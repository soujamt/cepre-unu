import { Badge } from '@/components/ui/badge';

type Variante = 'exito' | 'destructive' | 'aviso' | 'info' | 'secondary' | 'default';

/**
 * Color de cada estado del sistema, igual que las insignias de las pantallas Livewire.
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
    REGISTRADO: 'default',
    CERRADO: 'secondary',
};

export function InsigniaEstado({ estado, className }: { estado: string; className?: string }) {
    return (
        <Badge variant={variantes[estado.toUpperCase()] ?? 'secondary'} className={className}>
            {estado}
        </Badge>
    );
}
