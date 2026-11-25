import { Camera, CircleCheck } from '@keyline-icons/react/fill';
import { useRef } from 'react';
import type { DatosAsistente } from '@/components/asistente/tipos';
import type { ControlAsistente } from '@/components/asistente/use-asistente';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/**
 * Ficha del postulante con su foto, como la "revisión fotográfica" del sistema anterior.
 * Aparece desde que se conoce el DNI (paso 2). Si la foto ya se subió en Fotos con ese
 * DNI, se muestra sola; si falta, el personal puede subirla aquí.
 */
export function FichaPostulante({ asistente, control }: { asistente: DatosAsistente; control: ControlAsistente }) {
    const archivo = useRef<HTMLInputElement>(null);
    const persona = control.formularios.personaForm;
    const documento = String(persona.numero_documento ?? '');
    const nombre = [persona.apellido_paterno, persona.apellido_materno, persona.apellido_casada, persona.nombres].filter(Boolean).join(' ');
    const { actual, puedeCambiar } = asistente.foto;

    if (asistente.paso < 2 || documento === '') {
        return null;
    }

    return (
        <section className="shadow-card flex gap-4 rounded-2xl border border-zinc-200/80 bg-white p-4 lg:flex-col lg:items-center lg:text-center dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none">
            <div
                className={cn(
                    'relative flex aspect-[3/4] w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border lg:w-40',
                    actual ? 'border-zinc-200/80 dark:border-white/[0.08]' : 'border-dashed border-zinc-300 bg-zinc-50 dark:border-white/[0.12] dark:bg-white/[0.03]',
                )}
            >
                {actual ? <img src={actual} alt={`Foto de ${nombre || documento}`} className="size-full object-cover" /> : <Camera className="size-8 text-zinc-300 dark:text-zinc-600" />}
            </div>
            <div className="min-w-0 flex-1 space-y-2 lg:w-full">
                <div>
                    <p className="text-sm leading-snug font-semibold text-zinc-900 dark:text-white">{nombre || 'Postulante'}</p>
                    <p className="font-mono text-xs text-zinc-500 dark:text-zinc-400">DNI {documento}</p>
                </div>
                {actual ? (
                    <p className="inline-flex items-center gap-1.5 text-xs font-medium text-green-600 dark:text-green-400">
                        <CircleCheck className="size-3.5" />
                        Foto registrada
                    </p>
                ) : (
                    <p className="text-xs text-amber-600 dark:text-amber-400">{puedeCambiar ? 'Sin foto: súbala aquí o en Fotos con el DNI.' : 'La foto la registra el CEPRE-UNU.'}</p>
                )}
                {puedeCambiar && (
                    <>
                        <Button variant="outline" size="sm" className="w-full" onClick={() => archivo.current?.click()} disabled={control.procesando}>
                            <Camera className="size-4" />
                            {actual ? 'Cambiar foto' : 'Subir foto'}
                        </Button>
                        <input
                            ref={archivo}
                            type="file"
                            accept="image/jpeg,image/png"
                            className="sr-only"
                            onChange={(evento) => {
                                const foto = evento.target.files?.[0];

                                if (foto) {
                                    control.subirFoto(foto);
                                }

                                evento.target.value = '';
                            }}
                        />
                        {control.errores.fotoPostulante && <p className="text-xs text-red-500">{control.errores.fotoPostulante}</p>}
                    </>
                )}
            </div>
        </section>
    );
}
