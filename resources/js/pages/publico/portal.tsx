import { ChevronRight, Search, SquarePen } from '@keyline-icons/react/fill';
import { Enlace } from '@/components/enlace';
import type { Icono } from '@/lib/iconos';
import { consulta, inscripcion } from '@/routes/publico';

const opciones: { href: string; icono: Icono; titulo: string; texto: string }[] = [
    {
        href: inscripcion.url(),
        icono: SquarePen,
        titulo: 'Inscribirme',
        texto: 'Primero pague en el banco. Luego ingrese su DNI y el número de operación del voucher.',
    },
    {
        href: consulta.url(),
        icono: Search,
        titulo: 'Consultar mi inscripción',
        texto: 'Vea su estado, turno y aula, y descargue su ficha y su carnet.',
    },
];

export default function Portal() {
    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {opciones.map(({ href, icono: IconoOpcion, titulo, texto }) => (
                <Enlace
                    key={titulo}
                    href={href}
                    prefetch
                    className="group flex items-start gap-4 rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card transition-colors hover:border-zinc-300 focus-visible:ring-2 focus-visible:ring-index-500 focus-visible:outline-none dark:border-white/[0.06] dark:bg-zinc-900 dark:hover:border-white/15"
                >
                    <span className="flex size-11 shrink-0 items-center justify-center rounded-lg bg-index-50 text-index-600 dark:bg-index-500/10 dark:text-index-300">
                        <IconoOpcion className="size-6" />
                    </span>
                    <span className="min-w-0 space-y-1">
                        <span className="flex items-center gap-1 text-base font-semibold text-zinc-900 dark:text-white">
                            {titulo}
                            <ChevronRight className="size-4 text-zinc-400 transition-transform group-hover:translate-x-0.5" />
                        </span>
                        <span className="block text-sm text-pretty text-zinc-500 dark:text-zinc-400">{texto}</span>
                    </span>
                </Enlace>
            ))}
        </div>
    );
}

Portal.layout = {
    titulo: 'Portal del postulante',
    subtitulo: 'Inscríbase en línea o consulte su inscripción, su aula y sus documentos.',
};
