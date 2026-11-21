<?php

namespace App\Support\Inscripcion;

use App\Enums\SexoPersona;
use App\Enums\TipoLenguaMaternaPersona;
use App\Models\FotoPostulante;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\ReglaPago;
use App\Traits\InscripcionCrear\GestionaAsistenteInscripcion;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * Asistente de inscripción: reutiliza los traits de InscripcionCrear (pasos, reglas y
 * protecciones del pago) con su estado guardado en la sesión del servidor.
 *
 * El navegador solo puede cambiar los campos de los formularios (camposEditables) y
 * llamar a las acciones permitidas; el paso actual, el pago validado, la persona y la
 * inscripción los decide siempre el servidor.
 */
abstract class AsistenteInscripcion
{
    use GestionaAsistenteInscripcion;

    /** Errores de validación de la solicitud actual. */
    private MessageBag $errores;

    /** @var array<string, array<string, array<int, mixed>>> Reglas que fallaron, por campo. */
    private array $reglasFallidas = [];

    /** @var list<array{nombre: string, parametros: array<string|int, mixed>}> Eventos (avisos, preguntas). */
    private array $eventos = [];

    /** @var array<string, mixed> Resultados de las propiedades calculadas en esta solicitud. */
    private array $calculadas = [];

    private ?string $redireccion = null;

    public function __construct()
    {
        $this->errores = new MessageBag;
    }

    /**
     * Asistente nuevo con los catálogos cargados.
     */
    public static function iniciar(): static
    {
        $asistente = new static;
        $asistente->hidratarCatalogos();

        return $asistente;
    }

    /**
     * @param  array<string, mixed>  $estado
     */
    public static function desdeEstado(array $estado): static
    {
        $asistente = new static;

        foreach ($asistente->propiedadesPublicas() as $propiedad) {
            if (array_key_exists($propiedad, $estado)) {
                $asistente->{$propiedad} = $estado[$propiedad];
            }
        }

        return $asistente;
    }

    /**
     * Retoma una inscripción PENDIENTE desde su primer paso sin completar.
     */
    public function cargarInscripcion(int $idInscripcion): void
    {
        $inscripcion = Inscripcion::query()
            ->with([
                'persona.ubigeoNacimiento', 'persona.ubigeoDomicilio', 'proceso', 'sede', 'modalidadAdmision', 'carrera', 'turno',
                'paisColegio', 'colegio.ubigeo', 'obligacionesPago.reglaPago.conceptoPago', 'obligacionesPago.pagos.importacionBancoDetalle',
            ])
            ->findOrFail($idInscripcion);

        abort_if($inscripcion->persona === null, 404);

        $this->inscripcionId = $inscripcion->id_ins;
        $this->personaId = $inscripcion->persona->id_per;
        $this->cargarPersonaDesdeSistema($inscripcion->persona);
        $this->cargarInformacionGeneralExistente($inscripcion);
        $this->cargarPostulacionExistente($inscripcion);
        $this->cargarPagoExistente($inscripcion);
        $this->pasoActual = $this->pagoValidado ? 4 : 1;
    }

    /**
     * Estado que se guarda en la sesión: las propiedades públicas, sin archivos.
     *
     * @return array<string, mixed>
     */
    public function estado(): array
    {
        return collect($this->propiedadesPublicas())
            ->reject(fn (string $propiedad): bool => $propiedad === 'fotoPostulante')
            ->mapWithKeys(fn (string $propiedad): array => [$propiedad => $this->{$propiedad}])
            ->all();
    }

    /**
     * Portal del postulante (true) o asistente del personal (false).
     */
    abstract public function esModoPublico(): bool;

    /**
     * Raíces de los datos que el navegador puede cambiar.
     *
     * @return list<string>
     */
    public function camposEditables(): array
    {
        return ['personaForm', 'generalForm', 'postulacionForm', 'pagoForm', 'pagosSeleccionados', 'confirmacionDatos', 'ubigeoNacimientoLabel', 'ubigeoDomicilioLabel', 'colegioLabel'];
    }

    /**
     * Acciones que el navegador puede pedir.
     *
     * @return list<string>
     */
    public function accionesPermitidas(): array
    {
        return [
            'buscarPagosPasoPago', 'confirmarPasoPago', 'buscarDatosPersona', 'guardarPasoPersonal', 'guardarPasoGeneral',
            'guardarPasoPostulacion', 'confirmarGuardadoDuplicado', 'cancelarDuplicadoPostulacion', 'irPaso', 'irPasoAnterior',
            'cancelarYReiniciarWizard',
        ];
    }

    public function esEditable(string $ruta): bool
    {
        return in_array(Str::before($ruta, '.'), $this->camposEditables(), true);
    }

    /**
     * Cambia un dato del formulario y ejecuta su reacción (updated…), como al escribir en el campo.
     */
    public function establecer(string $ruta, mixed $valor): void
    {
        abort_unless($this->esEditable($ruta), 403, 'Este dato no se puede cambiar.');

        // Laravel convierte el texto vacío en null (ConvertEmptyStringsToNull); en el
        // asistente un campo vacío es siempre '', como lo esperan los traits.
        $valor ??= '';

        $raiz = Str::before($ruta, '.');

        if ($raiz === $ruta) {
            $this->{$raiz} = $valor;
        } else {
            $datos = $this->{$raiz};
            data_set($datos, Str::after($ruta, '.'), $valor);
            $this->{$raiz} = $datos;
        }

        $this->calculadas = [];

        $this->ejecutandoValidacion(function () use ($ruta, $raiz, $valor): void {
            $especifico = 'updated'.Str::studly(str_replace('.', '_', $ruta));

            if (method_exists($this, $especifico)) {
                $this->{$especifico}($valor);
            }

            $general = 'updated'.Str::studly($raiz);

            if ($raiz !== $ruta && method_exists($this, $general)) {
                $this->{$general}($valor, Str::after($ruta, '.'));
            }
        });
    }

    /**
     * Ejecuta una acción permitida; los parámetros sin tipo de clase se toman en orden.
     *
     * @param  list<mixed>  $parametros
     */
    public function ejecutar(string $accion, array $parametros = []): void
    {
        abort_unless(in_array($accion, $this->accionesPermitidas(), true), 403, 'Acción no permitida.');

        $nombrados = [];
        $posicion = 0;

        foreach ((new ReflectionMethod($this, $accion))->getParameters() as $parametro) {
            $tipo = $parametro->getType();

            if ($tipo instanceof ReflectionNamedType && ! $tipo->isBuiltin()) {
                continue;
            }

            if (array_key_exists($posicion, $parametros)) {
                $nombrados[$parametro->getName()] = $parametros[$posicion];
                $posicion++;
            }
        }

        $this->ejecutandoValidacion(fn () => app()->call([$this, $accion], $nombrados));
    }

    /**
     * Sube la foto del postulante desde el paso de revisión.
     */
    public function subirFoto(?UploadedFile $archivo): void
    {
        $this->fotoPostulante = $archivo;
        $this->ejecutandoValidacion(fn () => $this->updatedFotoPostulante());
    }

    public function errores(): MessageBag
    {
        return $this->errores;
    }

    /**
     * @return array<string, list<string>>
     */
    public function reglasFallidas(): array
    {
        return collect($this->reglasFallidas)->map(fn (array $reglas): array => array_map(fn (string $regla): string => Str::snake($regla), array_keys($reglas)))->all();
    }

    /**
     * @return list<array{nombre: string, parametros: array<string|int, mixed>}>
     */
    public function eventos(): array
    {
        return $this->eventos;
    }

    /**
     * Último aviso (toast) emitido en la solicitud.
     *
     * @return array{type: string, message: string}|null
     */
    public function ultimoAviso(): ?array
    {
        $aviso = collect($this->eventos)->last(fn (array $evento): bool => $evento['nombre'] === 'toast');

        return $aviso === null ? null : ['type' => (string) ($aviso['parametros']['type'] ?? 'info'), 'message' => (string) ($aviso['parametros']['message'] ?? '')];
    }

    public function redireccion(): ?string
    {
        return $this->redireccion;
    }

    /**
     * Datos que necesita la pantalla de React para el paso actual.
     *
     * @return array<string, mixed>
     */
    public function vista(): array
    {
        $contexto = $this->contextoProcesoPostulacion();
        $proceso = $contexto['proceso'];
        $regla = $this->reglaPagoSeleccionada();
        $foto = $this->fotoPostulanteActual();
        $modalidad = $this->modalidadPostulacion();
        $catalogo = fn ($modelos, string $id, string $nombre): array => $modelos
            ->map(fn ($modelo): array => ['valor' => (string) $modelo->{$id}, 'nombre' => (string) $modelo->{$nombre}])
            ->values()
            ->all();

        return [
            'modoPublico' => $this->esModoPublico(),
            'paso' => $this->pasoActual,
            'pasos' => collect($this->pasos())->map(fn (array $paso, int $numero): array => ['numero' => $numero, 'titulo' => $paso['titulo'], 'descripcion' => $paso['descripcion']])->values()->all(),
            'pagoValidado' => $this->pagoValidado,
            'tienePagoValidado' => $this->inscripcionId !== null && $this->tienePagoValidadoWizard(),
            'personaGuardada' => $this->personaId !== null,
            'editandoInscripcion' => $this->inscripcionId !== null,
            'busquedaPagoRealizada' => $this->busquedaPagoRealizada,
            'confirmacionDatos' => $this->confirmacionDatos,
            'reniec' => $this->reniecMensaje === null ? null : ['mensaje' => $this->reniecMensaje, 'tipo' => $this->reniecTipoMensaje],
            'origenDatosPersona' => $this->origenDatosPersona,
            'etiquetas' => ['ubigeoNacimiento' => $this->ubigeoNacimientoLabel, 'ubigeoDomicilio' => $this->ubigeoDomicilioLabel, 'colegio' => $this->colegioLabel],
            'formularios' => [
                'personaForm' => $this->personaForm,
                'generalForm' => $this->generalForm,
                'postulacionForm' => $this->postulacionForm,
                'pagoForm' => $this->pagoForm,
            ],
            'pagosSeleccionados' => collect($this->pagosSeleccionados)->filter()->keys()->map(fn (mixed $id): int => (int) $id)->values()->all(),
            'proceso' => [
                'disponible' => (bool) $contexto['disponible'],
                'mensaje' => (string) $contexto['mensaje'],
                'nombre' => $proceso?->nombre_pro,
                'apertura' => $proceso?->fecha_inicio_pro?->format('d/m/Y h:i a'),
                'cierre' => $proceso?->fecha_fin_pro?->format('d/m/Y h:i a'),
            ],
            'inicioBloqueado' => $this->inicioInscripcionBloqueado(),
            'pago' => [
                'reglas' => $this->reglasPagoDisponibles()->map(fn (ReglaPago $reglaPago): array => [
                    'valor' => (string) $reglaPago->id_rpa,
                    'nombre' => $this->descripcionReglaPago($reglaPago),
                    'documento' => $reglaPago->documento_requerido_rpa,
                ])->values()->all(),
                'documentoRequerido' => $regla?->documento_requerido_rpa,
                'montoMinimo' => $regla === null ? null : (float) $this->montoMinimoRegla($regla),
                'montoMarcado' => (float) $this->montoPagosSeleccionados(),
                'encontrados' => $this->detallesPagoEncontrados()->map(fn (ImportacionBancoDetalle $detalle): array => [
                    'id' => $detalle->id_ibd,
                    'monto' => (float) $detalle->monto_ibd,
                    'operacion' => $detalle->numero_operacion_visible_ibd ?: $detalle->numero_operacion_ibd,
                    'fecha' => $detalle->fecha_pago_ibd?->format('d/m/Y'),
                    'ubicacion' => $detalle->ubicacion_ibd,
                ])->values()->all(),
            ],
            'catalogos' => [
                'tiposDocumento' => $this->tiposDocumento()->map(fn ($tipo): array => [
                    'valor' => (string) $tipo->id_tdo,
                    'nombre' => (string) $tipo->nombre_tdo,
                    'longitudMinima' => $tipo->longitud_minima_tdo,
                    'longitudMaxima' => $tipo->longitud_maxima_tdo,
                ])->values()->all(),
                'tipoDocumentoDni' => (string) ($this->tiposDocumento()->first(fn ($tipo): bool => $this->esTipoDni($tipo))?->id_tdo ?? ''),
                'estadosCiviles' => $catalogo($this->estadosCiviles(), 'id_eci', 'nombre_eci'),
                'paises' => $catalogo($this->paises(), 'id_pai', 'nombre_pai'),
                'paisPeru' => $this->paisPeruId(),
                'nacionalidades' => $catalogo($this->nacionalidades(), 'id_nac', 'nombre_nac'),
                'identidadesEtnicas' => $catalogo($this->identidadesEtnicas(), 'id_iet', 'nombre_iet'),
                'lenguasNativas' => $catalogo($this->lenguasNativas(), 'id_lna', 'nombre_lna'),
                'lenguasExtranjeras' => $catalogo($this->lenguasExtranjeras(), 'id_lex', 'nombre_lex'),
                'tiposDiscapacidad' => $catalogo($this->tiposDiscapacidad(), 'id_tdi', 'nombre_tdi'),
                'tiposColegio' => $catalogo($this->tiposColegio(), 'id_tco', 'nombre_tco'),
                'sexos' => array_map(fn ($sexo): array => ['valor' => $sexo->value, 'nombre' => $sexo->getNombre()], SexoPersona::cases()),
                'lenguasMaternas' => array_map(fn ($tipo): array => ['valor' => $tipo->value, 'nombre' => $tipo->getNombre()], TipoLenguaMaternaPersona::cases()),
            ],
            'postulacion' => [
                'modalidad' => $modalidad === null ? null : ['nombre' => $modalidad->nombre_mad, 'condicion' => $modalidad->condicion_egreso_mad?->getNombre()],
                'sedes' => $this->sedesPostulacionDisponibles()->map(fn (array $sede): array => ['valor' => $sede['id'], 'nombre' => $sede['nombre']])->all(),
                'carreras' => $this->carrerasPostulacionDisponibles()->map(fn (array $carrera): array => ['valor' => $carrera['id'], 'nombre' => $carrera['nombre']])->all(),
                'turnos' => $this->turnosPostulacionDisponibles()->map(fn (array $turno): array => ['valor' => $turno['id'], 'nombre' => $turno['nombre']])->values()->all(),
            ],
            'foto' => [
                'puedeCambiar' => $this->puedeCambiarFoto(),
                'actual' => $foto instanceof FotoPostulante ? $this->fuenteFotoPostulante($foto) : null,
            ],
        ];
    }

    // --- Lo que los traits esperan de un componente (validar, errores, eventos, redirección) ---

    /**
     * @param  array<string, mixed>|null  $reglas
     * @param  array<string, string>  $mensajes
     * @param  array<string, string>  $atributos
     * @return array<string, mixed>
     */
    public function validate(?array $reglas = null, array $mensajes = [], array $atributos = []): array
    {
        $validador = Validator::make(
            $this->datosParaValidar(),
            $reglas ?? $this->rules(),
            $mensajes ?: ($reglas === null ? $this->messages() : []),
            $atributos,
        );

        if ($validador->fails()) {
            throw new ValidationException($validador);
        }

        return $validador->validated();
    }

    public function addError(string $clave, string $mensaje): void
    {
        $this->errores->add($clave, $mensaje);
    }

    /**
     * @param  string|list<string>|null  $claves
     */
    public function resetErrorBag(string|array|null $claves = null): void
    {
        if ($claves === null) {
            $this->errores = new MessageBag;
            $this->reglasFallidas = [];

            return;
        }

        $quitar = (array) $claves;
        $this->errores = new MessageBag(collect($this->errores->messages())->except($quitar)->all());
        $this->reglasFallidas = collect($this->reglasFallidas)->except($quitar)->all();
    }

    /**
     * @param  string|list<string>|null  $claves
     */
    public function resetValidation(string|array|null $claves = null): void
    {
        $this->resetErrorBag($claves);
    }

    public function setErrorBag(MessageBagContract|array $errores): void
    {
        $this->errores = new MessageBag($errores instanceof MessageBagContract ? $errores->messages() : $errores);
    }

    public function getErrorBag(): MessageBag
    {
        return $this->errores;
    }

    public function dispatch(string $evento, mixed ...$parametros): void
    {
        $this->eventos[] = ['nombre' => $evento, 'parametros' => $parametros];
    }

    public function reset(string|array ...$propiedades): void
    {
        $valores = (new ReflectionClass($this))->getDefaultProperties();

        foreach (collect($propiedades)->flatten() as $propiedad) {
            $this->{$propiedad} = $valores[$propiedad] ?? null;
        }

        $this->calculadas = [];
    }

    public function redirect(string $url, bool $navigate = false): void
    {
        $this->redireccion = $url;
    }

    /**
     * Las propiedades calculadas de los traits se leen como atributos: $this->procesoPostulacionActivo.
     */
    public function __get(string $nombre): mixed
    {
        abort_unless(method_exists($this, $nombre), 500, "Propiedad desconocida del asistente: {$nombre}");

        if (! array_key_exists($nombre, $this->calculadas)) {
            $this->calculadas[$nombre] = $this->{$nombre}();
        }

        return $this->calculadas[$nombre];
    }

    public function __unset(string $nombre): void
    {
        unset($this->calculadas[$nombre]);
    }

    /**
     * Los errores de validación quedan en el asistente y se devuelven con la respuesta.
     */
    private function ejecutandoValidacion(callable $accion): void
    {
        try {
            $accion();
        } catch (ValidationException $error) {
            foreach ($error->errors() as $campo => $mensajes) {
                foreach ($mensajes as $mensaje) {
                    $this->errores->add($campo, $mensaje);
                }
            }

            $this->reglasFallidas = array_merge($this->reglasFallidas, $error->validator->failed());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function datosParaValidar(): array
    {
        return collect($this->propiedadesPublicas())->mapWithKeys(fn (string $propiedad): array => [$propiedad => $this->{$propiedad}])->all();
    }

    /**
     * @return list<string>
     */
    private function propiedadesPublicas(): array
    {
        return collect((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC))
            ->reject(fn (ReflectionProperty $propiedad): bool => $propiedad->isStatic())
            ->map(fn (ReflectionProperty $propiedad): string => $propiedad->getName())
            ->values()
            ->all();
    }

    private function esTipoDni(mixed $tipo): bool
    {
        return in_array((string) $tipo->codigo_tdo, ['1', 'DNI'], true)
            || ((int) ($tipo->longitud_minima_tdo ?? 0) === 8
                && (int) ($tipo->longitud_maxima_tdo ?? 0) === 8
                && str_contains(mb_strtoupper((string) $tipo->nombre_tdo, 'UTF-8'), 'IDENTIDAD'));
    }
}
