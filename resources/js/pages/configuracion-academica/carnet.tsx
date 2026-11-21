import { useForm } from '@inertiajs/react';
import { FileText, ImagePlus, Save } from '@keyline-icons/react/fill';
import { useEffect, useState } from 'react';
import { CampoArea, CampoTexto } from '@/components/campo';
import { Panel } from '@/components/panel';
import { Button } from '@/components/ui/button';
import { usePermiso } from '@/hooks/use-permiso';
import { inicio } from '@/routes';
import { configuracion, guardar, muestra } from '@/routes/carnet';

interface PropsCarnet {
    carnet: { director_nombre: string; director_cargo: string; nota: string };
    firmaActual: string | null;
}

export default function Carnet({ carnet, firmaActual }: PropsCarnet) {
    const puedeEditar = usePermiso()('parametro_editar');
    const formulario = useForm<{ director_nombre: string; director_cargo: string; nota: string; firma: File | null }>({ ...carnet, firma: null });
    const [vistaPrevia, setVistaPrevia] = useState<string | null>(null);

    useEffect(() => {
        if (!formulario.data.firma) {
            setVistaPrevia(null);

            return;
        }

        const url = URL.createObjectURL(formulario.data.firma);
        setVistaPrevia(url);

        return () => URL.revokeObjectURL(url);
    }, [formulario.data.firma]);

    const enviar = (evento: React.FormEvent): void => {
        evento.preventDefault();
        formulario.post(guardar.url(), { preserveScroll: true, forceFormData: true, onSuccess: () => formulario.setData('firma', null) });
    };

    const imagen = vistaPrevia ?? firmaActual;

    return (
        <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <Panel titulo="Datos que se imprimen en todos los carnets" descripcion="El nombre del director se guarda en mayúsculas.">
                <form onSubmit={enviar} noValidate className="space-y-5">
                    <CampoTexto
                        etiqueta="Nombre del director"
                        value={formulario.data.director_nombre}
                        onChange={(evento) => formulario.setData('director_nombre', evento.target.value)}
                        error={formulario.errors.director_nombre}
                        disabled={!puedeEditar}
                        maxLength={120}
                        requerido
                    />
                    <CampoTexto
                        etiqueta="Cargo"
                        value={formulario.data.director_cargo}
                        onChange={(evento) => formulario.setData('director_cargo', evento.target.value)}
                        error={formulario.errors.director_cargo}
                        disabled={!puedeEditar}
                        maxLength={120}
                        requerido
                    />
                    <CampoArea
                        etiqueta="Nota al pie del carnet"
                        value={formulario.data.nota}
                        onChange={(evento) => formulario.setData('nota', evento.target.value)}
                        error={formulario.errors.nota}
                        disabled={!puedeEditar}
                        maxLength={200}
                    />

                    {puedeEditar && (
                        <div className="space-y-2">
                            <label
                                htmlFor="firma-sello"
                                className="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-zinc-300 bg-zinc-50/60 px-4 py-4 transition-colors hover:border-index-300 hover:bg-index-50/40 dark:border-white/15 dark:bg-white/[0.02] dark:hover:border-index-500/40"
                            >
                                <span className="flex size-10 items-center justify-center rounded-xl bg-white text-index-500 shadow-card ring-1 ring-zinc-200/80 dark:bg-white/[0.06] dark:ring-white/10">
                                    <ImagePlus className="size-5" />
                                </span>
                                <span className="min-w-0 text-sm">
                                    <span className="block font-medium text-zinc-800 dark:text-zinc-100">{formulario.data.firma ? formulario.data.firma.name : 'Subir imagen del sello y la firma'}</span>
                                    <span className="block text-xs text-zinc-500 dark:text-zinc-400">PNG, de preferencia con fondo transparente, hasta 1 MB.</span>
                                </span>
                            </label>
                            <input
                                id="firma-sello"
                                type="file"
                                accept="image/png"
                                className="sr-only"
                                onChange={(evento) => formulario.setData('firma', evento.target.files?.[0] ?? null)}
                            />
                            {formulario.errors.firma && <p className="text-xs font-medium text-red-500">{formulario.errors.firma}</p>}
                        </div>
                    )}

                    {puedeEditar && (
                        <div className="flex justify-end">
                            <Button type="submit" disabled={formulario.processing}>
                                <Save className="size-4.5" />
                                {formulario.processing ? 'Guardando…' : 'Guardar'}
                            </Button>
                        </div>
                    )}
                </form>
            </Panel>

            <Panel titulo="Vista previa" descripcion={vistaPrevia ? 'Imagen nueva, aún sin guardar.' : 'Sello y firma actuales.'}>
                <div className="flex min-h-36 items-center justify-center rounded-xl border border-zinc-200/80 bg-[repeating-conic-gradient(#f4f4f5_0_25%,#fff_0_50%)] bg-[length:16px_16px] p-4 dark:border-white/[0.08] dark:bg-[repeating-conic-gradient(#1c1c1e_0_25%,#141414_0_50%)]">
                    {imagen ? <img src={imagen} alt="Sello y firma" className="max-h-28 object-contain" /> : <p className="text-sm text-zinc-500 dark:text-zinc-400">Aún no se subió la imagen.</p>}
                </div>
                <Button variant="outline" asChild className="mt-4 w-full">
                    <a href={muestra.url()} target="_blank" rel="noopener">
                        <FileText className="size-4.5" />
                        Ver carnet de muestra (PDF)
                    </a>
                </Button>
            </Panel>
        </div>
    );
}

Carnet.layout = {
    titulo: 'Carnet',
    subtitulo: 'Director, cargo, nota y sello que se imprimen en el carnet del postulante.',
    icono: 'carnet',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Configuración académica' }, { titulo: 'Carnet', href: configuracion.url() }],
};
