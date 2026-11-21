import { Moon, Sun } from '@keyline-icons/react';
import { Button } from '@/components/ui/button';
import { useTema } from '@/hooks/use-tema';

/**
 * Alterna entre tema claro y oscuro.
 */
export function BotonTema() {
    const { oscuro, alternarTema } = useTema();

    return (
        <Button variant="ghost" size="icon" onClick={alternarTema} aria-label={oscuro ? 'Usar tema claro' : 'Usar tema oscuro'}>
            {oscuro ? <Moon className="size-5" /> : <Sun className="size-5" />}
        </Button>
    );
}
