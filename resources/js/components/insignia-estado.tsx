import { Lock, SquareCheck, SquareX } from '@keyline-icons/react/fill';
import { Badge } from '@/components/ui/badge';
import type { Icono } from '@/lib/iconos';

type Variante = 'exito' | 'destructive' | 'aviso' | 'info' | 'secondary' | 'default';

/**
 * Color de cada estado del sistema.
 */
const variantes: Record<string, Variante> = {
    HABILITADO: 'exito',
    ACTIVO: 'exito',
    PAGADO: 'exito',
    VERIFICADO: 'exito',
    PROCESADO: 'exito',
    VÁLIDO: 'exito',
    DESHABILITADO: 'destructive',
    ANULADO: 'destructive',
    RECHAZADO: 'destructive',
    OBSERVADO: 'destructive',
    'NO VÁLIDO': 'destructive',
    PENDIENTE: 'aviso',
    PARCIAL: 'aviso',
    'EN REVISIÓN': 'aviso',
    LIBRE: 'info',
    EXONERADO: 'info',
    'SIN REGLA': 'secondary',
    REGISTRADO: 'default',
    CERRADO: 'secondary',
};

/**
 * Íconos de los estados de los catálogos (habilitado / deshabilitado).
 */
const iconos: Record<string, Icono> = {
    HABILITADO: SquareCheck,
    DESHABILITADO: SquareX,
    CERRADO: Lock,
};

export function InsigniaEstado({ estado, className }: { estado: string; className?: string }) {
    const clave = estado.toUpperCase();
    const IconoEstado = iconos[clave];

    return (
        <Badge variant={variantes[clave] ?? 'secondary'} className={className}>
            {IconoEstado && <IconoEstado />}
            {estado}
        </Badge>
    );
}
