import { router } from '@inertiajs/react';
import { AsistenteInscripcion } from '@/components/asistente/asistente-inscripcion';
import type { DatosAsistente, PreguntaAsistente } from '@/components/asistente/tipos';
import { inicio } from '@/routes';
import { asistente as rutaAsistente, index } from '@/routes/inscripciones';
import { foto } from '@/routes/inscripciones/asistente';

export default function Asistente({ token, asistente, pregunta }: { token: string; asistente: DatosAsistente; pregunta: PreguntaAsistente | null }) {
    return (
        <AsistenteInscripcion
            asistente={asistente}
            pregunta={pregunta}
            rutaActualizar={rutaAsistente.url(token)}
            rutaFoto={foto.url(token)}
            alSalir={() => router.visit(index.url())}
        />
    );
}

Asistente.layout = {
    titulo: 'Nueva inscripción',
    subtitulo: 'Pago, datos del postulante, colegio y postulación, en cuatro pasos.',
    icono: 'nuevaInscripcion',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Inscripciones', href: index.url() }, { titulo: 'Nueva inscripción' }],
};
