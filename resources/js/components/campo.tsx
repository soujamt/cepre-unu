import { CircleAlert, Eye, EyeOff } from '@keyline-icons/react';
import { useId, useState } from 'react';
import type { InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import type { Icono } from '@/lib/iconos';
import { cn } from '@/lib/utils';

interface PropsCampo {
    etiqueta: string;
    error?: string;
    ayuda?: string;
    requerido?: boolean;
    className?: string;
}

/**
 * Clases del control con etiqueta flotante: 48 px de alto, el texto baja para dejar
 * sitio a la etiqueta y el borde inferior es un poco más oscuro.
 */
const claseControl = (invalido: boolean): string =>
    cn(
        'peer block w-full appearance-none rounded-lg border bg-control text-base text-zinc-700 shadow-xs sm:text-sm dark:text-zinc-200',
        'placeholder:text-transparent focus:ring-1 focus:ring-primary focus:ring-offset-1 focus:ring-offset-background focus:outline-hidden',
        'disabled:cursor-not-allowed disabled:bg-zinc-50 disabled:text-zinc-500 disabled:shadow-none dark:disabled:bg-white/5 dark:disabled:text-zinc-400',
        invalido
            ? 'border-red-500 focus:ring-red-500'
            : 'border-zinc-200 border-b-zinc-300/80 dark:border-white/10 dark:border-b-white/10',
    );

/**
 * La etiqueta queda centrada mientras el campo está vacío y sube al enfocarlo o
 * cuando tiene valor (con :placeholder-shown, por eso el placeholder es un espacio).
 */
const claseEtiqueta = (invalido: boolean, multilinea: boolean, conIcono: boolean): string =>
    cn(
        'pointer-events-none absolute z-10 origin-left transition-all duration-200 ease-out',
        conIcono ? 'start-10' : 'start-3.5',
        multilinea
            ? 'top-3.5 text-[13px] peer-focus:top-1.5 peer-focus:text-[10px] peer-[:not(:placeholder-shown)]:top-1.5 peer-[:not(:placeholder-shown)]:text-[10px]'
            : 'top-1/2 -translate-y-1/2 text-[13px] peer-focus:top-1.5 peer-focus:translate-y-0 peer-focus:text-[10px] peer-[:not(:placeholder-shown)]:top-1.5 peer-[:not(:placeholder-shown)]:translate-y-0 peer-[:not(:placeholder-shown)]:text-[10px]',
        invalido
            ? 'text-red-500 peer-focus:text-red-500'
            : 'text-zinc-500 peer-focus:text-primary dark:text-zinc-400 dark:peer-focus:text-white',
    );

function Etiqueta({ htmlFor, texto, requerido, className }: { htmlFor: string; texto: string; requerido?: boolean; className: string }) {
    return (
        <label htmlFor={htmlFor} className={className}>
            <span className="inline-flex items-center gap-1">
                {texto}
                {requerido && <span className="text-red-500">*</span>}
            </span>
        </label>
    );
}

function Mensaje({ id, error, ayuda }: { id: string; error?: string; ayuda?: string }) {
    if (error) {
        return (
            <p id={id} role="alert" className="flex items-center gap-1 ps-3 text-xs font-medium text-red-500 dark:text-red-400">
                <CircleAlert className="size-3.5 shrink-0" />
                {error}
            </p>
        );
    }

    return ayuda ? (
        <p id={id} className="ps-3 text-xs text-zinc-500 dark:text-zinc-400">
            {ayuda}
        </p>
    ) : null;
}

function Contenedor({ className, children, mensaje }: { className?: string; children: ReactNode; mensaje: ReactNode }) {
    return (
        <div className={cn('grid min-w-0 content-start gap-1.5', className)}>
            <div className="relative min-w-0">{children}</div>
            {mensaje}
        </div>
    );
}

type PropsCampoTexto = PropsCampo & Omit<InputHTMLAttributes<HTMLInputElement>, 'className' | 'placeholder'> & { iconoInicio?: Icono };

/**
 * Campo de texto con etiqueta flotante. Con type="password" agrega el botón para mostrar la clave.
 */
export function CampoTexto({ etiqueta, error, ayuda, requerido, className, iconoInicio: IconoInicio, type = 'text', id, ...input }: PropsCampoTexto) {
    const idGenerado = useId();
    const idCampo = id ?? idGenerado;
    const [mostrarClave, setMostrarClave] = useState(false);
    const esClave = type === 'password';
    const invalido = Boolean(error);

    return (
        <Contenedor className={className} mensaje={<Mensaje id={`${idCampo}-mensaje`} error={error} ayuda={ayuda} />}>
            {IconoInicio && (
                <IconoInicio
                    className={cn(
                        'pointer-events-none absolute start-3.5 top-1/2 size-4.5 -translate-y-1/2',
                        invalido ? 'text-red-400' : 'text-zinc-400 dark:text-white/60',
                    )}
                />
            )}
            <input
                {...input}
                id={idCampo}
                type={esClave && mostrarClave ? 'text' : type}
                placeholder=" "
                required={requerido}
                aria-invalid={invalido || undefined}
                aria-describedby={error || ayuda ? `${idCampo}-mensaje` : undefined}
                className={cn(claseControl(invalido), 'h-12 pt-5.5 pb-1.5 leading-5.5', IconoInicio ? 'ps-10' : 'ps-3.5', esClave ? 'pe-11' : 'pe-3.5')}
            />
            <Etiqueta htmlFor={idCampo} texto={etiqueta} requerido={requerido} className={claseEtiqueta(invalido, false, Boolean(IconoInicio))} />
            {esClave && (
                <button
                    type="button"
                    onClick={() => setMostrarClave((valor) => !valor)}
                    aria-label={mostrarClave ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                    className="absolute end-2 top-1/2 flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-zinc-400 hover:text-zinc-600 dark:text-white/60 dark:hover:text-white"
                >
                    {mostrarClave ? <EyeOff className="size-4.5" /> : <Eye className="size-4.5" />}
                </button>
            )}
        </Contenedor>
    );
}

type PropsCampoArea = PropsCampo & Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'className' | 'placeholder'>;

/**
 * Área de texto con etiqueta flotante; la etiqueta descansa arriba, no al centro.
 */
export function CampoArea({ etiqueta, error, ayuda, requerido, className, id, rows = 3, ...area }: PropsCampoArea) {
    const idGenerado = useId();
    const idCampo = id ?? idGenerado;
    const invalido = Boolean(error);

    return (
        <Contenedor className={className} mensaje={<Mensaje id={`${idCampo}-mensaje`} error={error} ayuda={ayuda} />}>
            <textarea
                {...area}
                id={idCampo}
                rows={rows}
                placeholder=" "
                required={requerido}
                aria-invalid={invalido || undefined}
                aria-describedby={error || ayuda ? `${idCampo}-mensaje` : undefined}
                className={cn(claseControl(invalido), 'min-h-12 resize-y px-3.5 pt-6 pb-2 leading-5.5')}
            />
            <Etiqueta htmlFor={idCampo} texto={etiqueta} requerido={requerido} className={claseEtiqueta(invalido, true, false)} />
        </Contenedor>
    );
}

type PropsCampoSelect = PropsCampo & Omit<SelectHTMLAttributes<HTMLSelectElement>, 'className'> & { children: ReactNode };

/**
 * Lista desplegable nativa con etiqueta flotante. Mientras el valor está vacío
 * (opción "Seleccionar …") la etiqueta queda al centro, como en los campos de texto.
 */
export function CampoSelect({ etiqueta, error, ayuda, requerido, className, id, children, value, defaultValue, onChange, ...select }: PropsCampoSelect) {
    const idGenerado = useId();
    const idCampo = id ?? idGenerado;
    const [valorLocal, setValorLocal] = useState(String(defaultValue ?? ''));
    const vacio = String(value ?? valorLocal) === '';
    const invalido = Boolean(error);

    return (
        <Contenedor className={className} mensaje={<Mensaje id={`${idCampo}-mensaje`} error={error} ayuda={ayuda} />}>
            <select
                {...select}
                id={idCampo}
                value={value}
                defaultValue={defaultValue}
                onChange={(evento) => {
                    setValorLocal(evento.target.value);
                    onChange?.(evento);
                }}
                required={requerido}
                data-vacio={vacio || undefined}
                aria-invalid={invalido || undefined}
                aria-describedby={error || ayuda ? `${idCampo}-mensaje` : undefined}
                className={cn(
                    claseControl(invalido),
                    'h-12 cursor-pointer bg-[url("data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23a1a1a1%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22/%3E%3C/svg%3E")] bg-[length:1rem] bg-[position:right_0.85rem_center] bg-no-repeat ps-3.5 pe-10 pt-5 pb-1.5 leading-5.5',
                    // Con la opción vacía el texto "Seleccionar …" se oculta y solo se ve la etiqueta;
                    // las opciones de la lista mantienen su color.
                    'data-vacio:text-transparent [&>option]:text-zinc-700 dark:[&>option]:bg-zinc-900 dark:[&>option]:text-zinc-200',
                )}
            >
                {children}
            </select>
            <label
                htmlFor={idCampo}
                className={cn(
                    'pointer-events-none absolute start-3.5 z-10 origin-left transition-all duration-200 ease-out',
                    vacio ? 'top-1/2 -translate-y-1/2 text-[13px]' : 'top-1.5 text-[10px]',
                    invalido ? 'text-red-500' : 'text-zinc-500 peer-focus:text-primary dark:text-zinc-400 dark:peer-focus:text-white',
                )}
            >
                <span className="inline-flex items-center gap-1">
                    {etiqueta}
                    {requerido && <span className="text-red-500">*</span>}
                </span>
            </label>
        </Contenedor>
    );
}

interface PropsCampoCasilla {
    etiqueta: string;
    descripcion?: string;
    marcado: boolean;
    alCambiar: (marcado: boolean) => void;
    error?: string;
    className?: string;
}

/**
 * Casilla de verificación dentro de una tarjeta, con título y una línea que explica la opción.
 */
export function CampoCasilla({ etiqueta, descripcion, marcado, alCambiar, error, className }: PropsCampoCasilla) {
    const idCampo = useId();

    return (
        <div className={cn('grid gap-1.5', className)}>
            <label
                htmlFor={idCampo}
                className="flex cursor-pointer items-start gap-3 rounded-md border border-zinc-200/80 bg-zinc-50/80 px-4 py-3 transition-colors hover:bg-zinc-100/80 dark:border-white/6 dark:bg-white/4 dark:hover:bg-white/7"
            >
                <Checkbox
                    id={idCampo}
                    checked={marcado}
                    onCheckedChange={(valor) => alCambiar(valor === true)}
                    aria-invalid={Boolean(error) || undefined}
                    aria-describedby={error ? `${idCampo}-mensaje` : undefined}
                    className="mt-0.5"
                />
                <span className="space-y-0.5">
                    <span className="block text-sm font-medium text-zinc-800 dark:text-zinc-100">{etiqueta}</span>
                    {descripcion && <span className="block text-sm text-zinc-500 dark:text-zinc-400">{descripcion}</span>}
                </span>
            </label>
            <Mensaje id={`${idCampo}-mensaje`} error={error} />
        </div>
    );
}
