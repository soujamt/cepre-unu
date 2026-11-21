import { Check, ChevronDown, CircleAlert, LoaderCircle } from '@keyline-icons/react/fill';
import { useEffect, useId, useRef, useState } from 'react';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export interface OpcionCombobox {
    valor: string;
    etiqueta: string;
    detalle?: string;
}

interface PropsCombobox {
    etiqueta: string;
    valor: string;
    alCambiar: (valor: string, opcion: OpcionCombobox | null) => void;
    /** Opciones fijas; se filtran en el navegador. */
    opciones?: OpcionCombobox[];
    /** Búsqueda en el servidor (por ejemplo ubigeos o colegios); se llama al escribir. */
    buscar?: (texto: string) => Promise<OpcionCombobox[]>;
    /** Texto que se muestra para el valor actual cuando las opciones vienen del servidor. */
    etiquetaSeleccionada?: string;
    placeholderBusqueda?: string;
    textoVacio?: string;
    error?: string;
    requerido?: boolean;
    deshabilitado?: boolean;
    className?: string;
}

/**
 * Selector con búsqueda y etiqueta flotante, de la misma altura que los demás campos (48 px).
 */
export function Combobox({
    etiqueta,
    valor,
    alCambiar,
    opciones,
    buscar,
    etiquetaSeleccionada,
    placeholderBusqueda = 'Buscar…',
    textoVacio = 'Sin resultados.',
    error,
    requerido,
    deshabilitado,
    className,
}: PropsCombobox) {
    const id = useId();
    const [abierto, setAbierto] = useState(false);
    const [texto, setTexto] = useState('');
    const [remotas, setRemotas] = useState<OpcionCombobox[]>([]);
    const [buscando, setBuscando] = useState(false);
    const ultimaBusqueda = useRef(0);

    const lista = buscar ? remotas : (opciones ?? []);
    const seleccionada = lista.find((opcion) => opcion.valor === valor) ?? (opciones ?? []).find((opcion) => opcion.valor === valor);
    const textoSeleccion = seleccionada?.etiqueta ?? (valor !== '' ? etiquetaSeleccionada : undefined);
    const vacio = !textoSeleccion;
    const invalido = Boolean(error);

    useEffect(() => {
        if (!buscar || !abierto) {
            return;
        }

        const numero = ++ultimaBusqueda.current;
        const espera = setTimeout(() => {
            setBuscando(true);
            buscar(texto)
                .then((resultado) => {
                    if (numero === ultimaBusqueda.current) {
                        setRemotas(resultado);
                    }
                })
                .finally(() => {
                    if (numero === ultimaBusqueda.current) {
                        setBuscando(false);
                    }
                });
        }, 300);

        return () => clearTimeout(espera);
    }, [buscar, texto, abierto]);

    return (
        <div className={cn('grid min-w-0 content-start gap-1.5', className)}>
            <Popover open={abierto} onOpenChange={setAbierto}>
                <PopoverTrigger asChild disabled={deshabilitado}>
                    <button
                        id={id}
                        type="button"
                        role="combobox"
                        aria-label={etiqueta}
                        aria-expanded={abierto}
                        aria-invalid={invalido || undefined}
                        className={cn(
                            'relative flex h-12 w-full min-w-0 items-end rounded-lg border bg-control ps-3.5 pe-10 pb-1.5 text-start text-base text-zinc-700 shadow-[0_1px_2px_rgba(16,24,40,0.05)] transition-[border-color,box-shadow] sm:text-sm dark:text-zinc-200 dark:shadow-none',
                            'focus-visible:border-index-400 focus-visible:ring-3 focus-visible:ring-index-500/15 focus-visible:outline-hidden data-[state=open]:border-index-400 data-[state=open]:ring-3 data-[state=open]:ring-index-500/15',
                            'disabled:cursor-not-allowed disabled:bg-zinc-50 disabled:text-zinc-500 dark:disabled:bg-white/5',
                            invalido ? 'border-red-400' : 'border-zinc-200 dark:border-white/10',
                        )}
                    >
                        <span
                            className={cn(
                                'pointer-events-none absolute start-3.5 transition-all duration-200 ease-out',
                                vacio ? 'top-1/2 -translate-y-1/2 text-[13px]' : 'top-1.5 text-[10px]',
                                invalido ? 'text-red-500' : 'text-zinc-500 dark:text-zinc-400',
                            )}
                        >
                            {etiqueta}
                            {requerido && <span className="ms-1 text-red-500">*</span>}
                        </span>
                        <span className="block truncate leading-5.5">{textoSeleccion ?? ' '}</span>
                        <ChevronDown className="pointer-events-none absolute end-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
                    </button>
                </PopoverTrigger>
                <PopoverContent className="w-(--radix-popover-trigger-width) min-w-80 p-0" align="start">
                    <Command shouldFilter={!buscar}>
                        <CommandInput placeholder={placeholderBusqueda} value={texto} onValueChange={setTexto} />
                        <CommandList>
                            {buscando ? (
                                <div className="flex items-center justify-center gap-2 py-6 text-sm text-zinc-500">
                                    <LoaderCircle className="size-4 animate-spin" />
                                    Buscando…
                                </div>
                            ) : (
                                <CommandEmpty>{textoVacio}</CommandEmpty>
                            )}
                            <CommandGroup>
                                {lista.map((opcion) => (
                                    <CommandItem
                                        key={opcion.valor}
                                        value={`${opcion.etiqueta} ${opcion.detalle ?? ''} ${opcion.valor}`}
                                        onSelect={() => {
                                            alCambiar(opcion.valor, opcion);
                                            setAbierto(false);
                                        }}
                                    >
                                        <span className="min-w-0 flex-1">
                                            <span className="block truncate">{opcion.etiqueta}</span>
                                            {opcion.detalle && <span className="block truncate text-xs text-zinc-500">{opcion.detalle}</span>}
                                        </span>
                                        <Check className={cn('size-4', opcion.valor === valor ? 'opacity-100' : 'opacity-0')} />
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            {error && (
                <p role="alert" className="flex items-center gap-1 ps-3 text-xs font-medium text-red-500 dark:text-red-400">
                    <CircleAlert className="size-3.5 shrink-0" />
                    {error}
                </p>
            )}
        </div>
    );
}
