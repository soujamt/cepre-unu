import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

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
 * Insignia de estado: pastilla redondeada con un punto del color del estado.
 */
export function InsigniaEstado({ estado, className }: { estado: string; className?: string }) {
    const variante = variantes[estado.toUpperCase()] ?? 'secondary';

    return (
        <Badge variant={variante} className={cn('gap-1.5 rounded-full ps-2 pe-2.5 text-[11.5px] font-semibold tracking-wide', className)}>
            <span className="relative flex size-1.5">
                {variante === 'aviso' && <span className="absolute inset-0 animate-ping rounded-full bg-current opacity-50" />}
                <span className="relative size-1.5 rounded-full bg-current" />
            </span>
            {estado}
        </Badge>
    );
}
