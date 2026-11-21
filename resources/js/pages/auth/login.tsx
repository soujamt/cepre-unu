import { useForm, usePage } from '@inertiajs/react';
import { Alert, Lock, Mail, ShieldCheck } from '@keyline-icons/react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { CampoTexto } from '@/components/campo';
import { Enlace } from '@/components/enlace';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { store } from '@/routes/login';
import { portal } from '@/routes/publico';

/**
 * Cuenta regresiva del bloqueo por intentos fallidos que envía el servidor.
 */
function useBloqueo(): number {
    const { segundosBloqueo } = usePage().flash;
    const [restantes, setRestantes] = useState(0);

    useEffect(() => {
        if (!segundosBloqueo) {
            return;
        }

        setRestantes(segundosBloqueo);
        const intervalo = setInterval(() => {
            setRestantes((actual) => {
                if (actual <= 1) {
                    clearInterval(intervalo);

                    return 0;
                }

                return actual - 1;
            });
        }, 1000);

        return () => clearInterval(intervalo);
    }, [segundosBloqueo]);

    return restantes;
}

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({ correo: '', password: '' });
    const segundos = useBloqueo();
    const bloqueado = segundos > 0;

    const enviar = (evento: FormEvent): void => {
        evento.preventDefault();
        post(store.url(), { onFinish: () => setData('password', '') });
    };

    return (
        <>
            <div className="space-y-2">
                <p className="text-sm text-zinc-500 dark:text-zinc-400">Bienvenido de nuevo</p>
                <h1 className="text-[1.75rem] leading-tight font-semibold tracking-tight text-zinc-900 dark:text-white">Inicia sesión</h1>
                <p className="text-sm leading-6 text-zinc-500 dark:text-zinc-400">Accede con tu cuenta institucional para continuar.</p>
            </div>

            {bloqueado && (
                <div role="alert" className="mt-6 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 dark:border-amber-400/20 dark:bg-amber-400/10">
                    <Alert className="mt-px size-5 shrink-0 text-amber-500" />
                    <p className="text-sm text-amber-700 dark:text-amber-300">
                        Demasiados intentos. Intenta nuevamente en <span className="font-semibold tabular-nums">{segundos}</span> segundos.
                    </p>
                </div>
            )}

            <form onSubmit={enviar} noValidate inert={bloqueado} className={cn('mt-8 space-y-6', bloqueado && 'opacity-55')}>
                <div className="space-y-4">
                    <CampoTexto
                        etiqueta="Correo electrónico"
                        type="email"
                        name="correo"
                        autoComplete="email"
                        iconoInicio={Mail}
                        value={data.correo}
                        onChange={(evento) => setData('correo', evento.target.value)}
                        error={errors.correo}
                        requerido
                        autoFocus
                    />
                    <CampoTexto
                        etiqueta="Contraseña"
                        type="password"
                        name="password"
                        autoComplete="current-password"
                        iconoInicio={Lock}
                        value={data.password}
                        onChange={(evento) => setData('password', evento.target.value)}
                        error={errors.password}
                        requerido
                    />
                </div>

                <Button type="submit" className="h-11 w-full" disabled={processing}>
                    {processing ? 'Validando acceso...' : 'Ingresar a la plataforma'}
                </Button>

                <div className="flex items-center gap-3 text-xs text-zinc-400 dark:text-zinc-500">
                    <span className="h-px flex-1 bg-zinc-200 dark:bg-white/10" />
                    <span className="inline-flex items-center gap-1.5">
                        <ShieldCheck className="size-4 text-index-500" />
                        Acceso seguro institucional
                    </span>
                    <span className="h-px flex-1 bg-zinc-200 dark:bg-white/10" />
                </div>
            </form>

            <p className="mt-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                ¿Eres postulante?{' '}
                <Enlace href={portal.url()} className="font-medium text-zinc-900 underline underline-offset-4 dark:text-white">
                    Inscríbete o consulta tu aula
                </Enlace>
            </p>
        </>
    );
}

Login.layout = { titulo: 'Inicio de sesión' };
