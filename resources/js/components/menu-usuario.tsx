import { router, usePage } from '@inertiajs/react';
import { ChevronDown, Power } from '@keyline-icons/react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { logout } from '@/routes';

function cerrarSesion(): void {
    router.post(logout.url());
}

function AvatarUsuario({ iniciales }: { iniciales: string }) {
    return (
        <span className="relative shrink-0">
            <Avatar className="size-8">
                <AvatarFallback className="bg-index-500 text-xs font-medium text-white">{iniciales}</AvatarFallback>
            </Avatar>
            <span className="absolute -end-0.5 -bottom-0.5 size-2.5 rounded-full bg-green-500 ring-2 ring-white dark:ring-zinc-900" />
        </span>
    );
}

/**
 * Tarjeta del usuario al pie del menú lateral, con el botón para cerrar sesión.
 */
export function TarjetaUsuario() {
    const { user } = usePage().props.auth;

    if (!user) {
        return null;
    }

    return (
        <div className="flex items-center justify-between gap-2">
            <div className="flex min-w-0 items-center gap-2.5">
                <AvatarUsuario iniciales={user.iniciales} />
                <div className="min-w-0 leading-tight">
                    <p className="truncate text-sm font-medium text-zinc-900 dark:text-white">{user.nombre}</p>
                    <p className="truncate text-xs text-zinc-500 dark:text-zinc-400">{user.correo}</p>
                </div>
            </div>
            <Tooltip>
                <TooltipTrigger asChild>
                    <Button variant="ghost" size="icon-sm" onClick={cerrarSesion} aria-label="Cerrar sesión" className="text-zinc-400 hover:text-red-500">
                        <Power className="size-4.5" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Cerrar sesión</TooltipContent>
            </Tooltip>
        </div>
    );
}

/**
 * Menú del usuario para pantallas pequeñas, en el encabezado.
 */
export function MenuUsuarioMovil() {
    const { user } = usePage().props.auth;

    if (!user) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-10 gap-1.5 px-1.5" aria-label="Menú del usuario">
                    <AvatarUsuario iniciales={user.iniciales} />
                    <ChevronDown className="size-4 text-zinc-400" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-60">
                <DropdownMenuLabel className="flex items-center gap-3 font-normal">
                    <AvatarUsuario iniciales={user.iniciales} />
                    <span className="min-w-0 leading-tight">
                        <span className="block truncate text-sm font-medium text-zinc-900 dark:text-white">Bienvenido nuevamente</span>
                        <span className="block truncate text-xs text-zinc-500 dark:text-zinc-400">{user.correo}</span>
                    </span>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem variant="destructive" onSelect={cerrarSesion}>
                    <Power />
                    Cerrar sesión
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
