import type { Opcion } from '@/types';

export type Formularios = {
    personaForm: Record<string, string | boolean>;
    generalForm: Record<string, string | number>;
    postulacionForm: Record<string, string>;
    pagoForm: Record<string, string>;
};

export interface DatosAsistente {
    modoPublico: boolean;
    paso: number;
    pasos: { numero: number; titulo: string; descripcion: string }[];
    pagoValidado: boolean;
    tienePagoValidado: boolean;
    personaGuardada: boolean;
    editandoInscripcion: boolean;
    busquedaPagoRealizada: boolean;
    confirmacionDatos: boolean;
    reniec: { mensaje: string; tipo: string } | null;
    origenDatosPersona: string | null;
    etiquetas: { ubigeoNacimiento: string; ubigeoDomicilio: string; colegio: string };
    formularios: Formularios;
    pagosSeleccionados: number[];
    proceso: { disponible: boolean; mensaje: string; nombre: string | null; apertura: string | null; cierre: string | null };
    inicioBloqueado: boolean;
    pago: {
        reglas: (Opcion & { documento: string | null })[];
        documentoRequerido: string | null;
        montoMinimo: number | null;
        montoMarcado: number;
        encontrados: { id: number; monto: number; operacion: string | null; fecha: string | null; ubicacion: string | null }[];
    };
    catalogos: {
        tiposDocumento: (Opcion & { longitudMinima: number | null; longitudMaxima: number | null })[];
        tipoDocumentoDni: string;
        estadosCiviles: Opcion[];
        paises: Opcion[];
        paisPeru: string;
        nacionalidades: Opcion[];
        identidadesEtnicas: Opcion[];
        lenguasNativas: Opcion[];
        lenguasExtranjeras: Opcion[];
        tiposDiscapacidad: Opcion[];
        tiposColegio: Opcion[];
        sexos: Opcion[];
        lenguasMaternas: Opcion[];
    };
    postulacion: { modalidad: { nombre: string; condicion: string | null } | null; sedes: Opcion[]; carreras: Opcion[]; turnos: Opcion[] };
    foto: { puedeCambiar: boolean; actual: string | null };
    registrada?: {
        codigo: string;
        estado: string;
        proceso: string | null;
        sede: string | null;
        carrera: string | null;
        turno: string | null;
        aula: string | null;
        documentos: { ficha: string; carnet?: string | null };
    } | null;
}

export interface PreguntaAsistente {
    titulo: string;
    mensaje: string;
    textoConfirmar: string;
    textoCancelar: string;
    confirmar: string | null;
    cancelar: string | null;
}

export type ValorCampo = string | number | boolean | null;

export type Cambio = { ruta: string; valor: ValorCampo };
