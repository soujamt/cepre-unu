<?php

namespace App\Http\Controllers\ConfiguracionAcademica;

use App\Exports\PlantillaCodigosAdmisionExport;
use App\Http\Controllers\Concerns\EligeProceso;
use App\Http\Controllers\Controller;
use App\Models\Proceso;
use App\Support\CodigosAdmisionProceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Códigos que Admisión usa para el lugar (sede + modalidad) y para cada carrera ofrecida.
 */
class CodigoAdmisionController extends Controller
{
    use EligeProceso;

    public function __construct(private CodigosAdmisionProceso $codigosAdmision) {}

    public function index(Request $request): Response
    {
        $proceso = $this->procesoElegido($request);
        $filas = $proceso === null ? collect() : $this->codigosAdmision->combinaciones($proceso);

        return Inertia::render('configuracion-academica/codigos-admision', [
            'procesos' => $this->opcionesProceso(),
            'proceso' => $this->resumenProceso($proceso),
            'filas' => $filas->map(fn (array $fila): array => [
                'clave' => $fila['clave'],
                'esLugar' => $fila['tipo'] === CodigosAdmisionProceso::TIPO_LUGAR,
                'sede' => $fila['sede'],
                'modalidad' => $fila['modalidad'],
                'carrera' => $fila['carrera'],
                'codigo' => (string) $fila['codigo'],
            ])->values(),
            'avance' => [
                'total' => $filas->count(),
                'conCodigo' => $filas->filter(fn (array $fila): bool => filled($fila['codigo']))->count(),
            ],
            'erroresPlantilla' => $request->session()->get('erroresPlantilla', []),
        ]);
    }

    public function update(Request $request, Proceso $proceso): RedirectResponse
    {
        $datos = $request->validate(
            ['codigos' => ['present', 'array'], 'codigos.*' => ['nullable', 'string', 'max:20']],
            ['codigos.*.max' => 'El código no debe superar 20 caracteres.'],
        );

        $guardados = $this->codigosAdmision->guardar($proceso, array_map(fn (mixed $codigo): string => (string) $codigo, $datos['codigos']));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Códigos guardados: {$guardados}."]);

        return back();
    }

    public function plantilla(Proceso $proceso): BinaryFileResponse
    {
        return Excel::download(
            new PlantillaCodigosAdmisionExport($this->codigosAdmision->combinaciones($proceso)),
            'codigos-admision-'.Str::slug($proceso->nombre_pro).'.xlsx',
        );
    }

    /**
     * Guarda los códigos de la plantilla llena y devuelve las filas que no se pudieron usar.
     */
    public function cargarPlantilla(Request $request, Proceso $proceso): RedirectResponse
    {
        $request->validate(
            ['plantilla' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048']],
            [
                'plantilla.required' => 'Seleccione la plantilla en Excel.',
                'plantilla.mimes' => 'Suba la plantilla en Excel (.xlsx).',
                'plantilla.max' => 'El archivo no debe pesar más de 2 MB.',
            ],
        );

        $lectura = $this->codigosAdmision->leerPlantilla($request->file('plantilla')->getRealPath(), $proceso);

        if ($lectura['codigos'] === []) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'La plantilla no trae códigos para este proceso.']);

            return back()->with('erroresPlantilla', $lectura['errores']);
        }

        $guardados = $this->codigosAdmision->guardar($proceso, $lectura['codigos']);

        Inertia::flash('toast', [
            'type' => $lectura['errores'] === [] ? 'success' : 'warning',
            'message' => "Plantilla cargada: {$guardados} código(s) guardado(s).",
        ]);

        return back()->with('erroresPlantilla', $lectura['errores']);
    }
}
