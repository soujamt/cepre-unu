import { router } from '@inertiajs/react';
import { AsistenteInscripcion } from '@/components/asistente/asistente-inscripcion';
import type { DatosAsistente, PreguntaAsistente } from '@/components/asistente/tipos';
import { portal } from '@/routes/publico';
import { asistente as rutaAsistente } from '@/routes/publico/inscripcion';

export default function InscripcionEnLinea({ token, asistente, pregunta }: { token: string; asistente: DatosAsistente; pregunta: PreguntaAsistente | null }) {
    return <AsistenteInscripcion asistente={asistente} pregunta={pregunta} rutaActualizar={rutaAsistente.url(token)} alSalir={() => router.visit(portal.url())} />;
}

InscripcionEnLinea.layout = {
    titulo: 'Inscripción en línea',
    subtitulo: 'Valide su pago y complete sus datos para inscribirse al CEPRE-UNU.',
};
