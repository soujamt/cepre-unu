<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\Concerns\EligeProceso;
use App\Models\FotoPostulante;
use App\Models\Inscripcion;
use App\Models\Persona;
use App\Support\ProcesadorFotoPostulante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Fotos de los postulantes para el carnet: subida en lote (el DNI sale del nombre del
 * archivo), reemplazo, eliminación y descarga en ZIP para la imprenta.
 */
class FotoController extends CatalogoController
{
    use EligeProceso;

    public function __construct(private ProcesadorFotoPostulante $procesador) {}

    public function index(Request $request): Response
    {
        $filtros = $this->filtrosFotos($request);
        $fotos = $this->consulta($filtros)->orderByDesc('actualizado_en')->paginate(24)->withQueryString();

        $nombres = Persona::query()
            ->whereIn('numero_documento_per', $fotos->getCollection()->pluck('numero_documento_fot'))
            ->get(['numero_documento_per', 'apellido_paterno_per', 'apellido_materno_per', 'nombres_per'])
            ->mapWithKeys(fn (Persona $persona): array => [
                $persona->numero_documento_per => trim("{$persona->apellido_paterno_per} {$persona->apellido_materno_per}, {$persona->nombres_per}"),
            ]);

        return Inertia::render('inscripciones/fotos', [
            'fotos' => $fotos->through(fn (FotoPostulante $foto): array => [
                'id' => $foto->id_fot,
                'documento' => $foto->numero_documento_fot,
                'nombre' => $nombres->get($foto->numero_documento_fot),
                'url' => $foto->url(),
                'actualizado' => $foto->actualizado_en?->locale('es')->diffForHumans(),
            ]),
            'filtros' => $filtros,
            'total' => $this->consulta($filtros)->count(),
            'procesos' => $this->opcionesProceso(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'archivos' => ['required', 'array', 'min:1', 'max:50'],
            'archivos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'documentos' => ['required', 'array', 'size:'.count((array) $request->file('archivos', []))],
            'documentos.*' => ['required', 'regex:/^\d{8,12}$/', 'distinct'],
        ], [
            'archivos.required' => 'Seleccione al menos una foto.',
            'archivos.max' => 'Suba como máximo 50 fotos a la vez.',
            'archivos.*.image' => 'El archivo debe ser una imagen.',
            'archivos.*.mimes' => 'La foto debe ser JPG o PNG.',
            'archivos.*.max' => 'Cada foto debe pesar como máximo 2 MB.',
            'documentos.size' => 'Indique el DNI de cada foto.',
            'documentos.*.required' => 'Escriba el DNI de la foto.',
            'documentos.*.regex' => 'El DNI debe tener entre 8 y 12 dígitos.',
            'documentos.*.distinct' => 'Hay dos fotos con el mismo DNI.',
        ]);

        $documentos = $request->input('documentos');

        foreach ($request->file('archivos') as $indice => $archivo) {
            try {
                $this->procesador->guardar($archivo, $documentos[$indice]);
            } catch (RuntimeException $error) {
                return back()->withErrors(["archivos.{$indice}" => $error->getMessage()]);
            }
        }

        return $this->volverCon(count($documentos).' foto(s) guardada(s) correctamente.');
    }

    public function destroy(FotoPostulante $foto): RedirectResponse
    {
        $this->procesador->eliminar($foto);

        return $this->volverCon('Foto eliminada correctamente.');
    }

    /**
     * ZIP con las fotos filtradas, nombradas DNI.jpg, para la imprenta.
     */
    public function exportar(Request $request): BinaryFileResponse
    {
        $ruta = tempnam(sys_get_temp_dir(), 'fotos').'.zip';
        $zip = new ZipArchive;
        $zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $disco = Storage::disk(FotoPostulante::DISCO);

        foreach ($this->consulta($this->filtrosFotos($request))->get() as $foto) {
            if ($disco->exists($foto->archivo_fot)) {
                $zip->addFromString("{$foto->numero_documento_fot}.jpg", (string) $disco->get($foto->archivo_fot));
            }
        }

        $zip->close();

        return response()->download($ruta, 'fotos-cepre-'.now()->format('Ymd-His').'.zip')->deleteFileAfterSend();
    }

    /**
     * @return array{buscar: string, proceso: string}
     */
    private function filtrosFotos(Request $request): array
    {
        $proceso = (string) $request->query('proceso', '');

        return [
            'buscar' => preg_replace('/\D+/', '', (string) $request->query('buscar', '')) ?? '',
            'proceso' => ctype_digit($proceso) ? $proceso : '',
        ];
    }

    /**
     * @param  array{buscar: string, proceso: string}  $filtros
     */
    private function consulta(array $filtros): Builder
    {
        return FotoPostulante::query()
            ->when($filtros['buscar'] !== '', fn (Builder $query) => $query->where('numero_documento_fot', 'like', "%{$filtros['buscar']}%"))
            ->when($filtros['proceso'] !== '', fn (Builder $query) => $query->whereIn(
                'numero_documento_fot',
                Inscripcion::query()
                    ->where('id_proceso', (int) $filtros['proceso'])
                    ->join('tbl_persona', 'tbl_persona.id_per', '=', 'tbl_inscripcion.id_persona')
                    ->select('tbl_persona.numero_documento_per'),
            ));
    }
}
