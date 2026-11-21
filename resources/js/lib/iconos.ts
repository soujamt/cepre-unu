import type { IconProps } from '@keyline-icons/react';
import { ChartBar, ClipboardList, Folder, Home, Settings, ShieldCheck } from '@keyline-icons/react';
import {
    ChartBar as ChartBarRelleno,
    ClipboardList as ClipboardListRelleno,
    Folder as FolderRelleno,
    Home as HomeRelleno,
    Settings as SettingsRelleno,
    ShieldCheck as ShieldCheckRelleno,
} from '@keyline-icons/react/fill';
import {
    Banknote,
    Building,
    CalendarDays,
    Camera,
    ChartBar as ChartBarDosTonos,
    ClipboardCheck,
    ClipboardList as ClipboardListDosTonos,
    Clock,
    CreditCard,
    FileSpreadsheet,
    GraduationCap,
    Home as HomeDosTonos,
    IdCard,
    LayoutDashboard,
    ListChecks,
    MapPin,
    School,
    ShieldCheck as ShieldCheckDosTonos,
    ShieldKey,
    ShieldUser,
    Users,
    Wallet,
} from '@keyline-icons/react/two-tone';
import type { ComponentType } from 'react';

export type Icono = ComponentType<IconProps>;

/**
 * Íconos de los grupos del menú lateral: trazo normal y relleno cuando el grupo está activo.
 */
export const iconosMenu = {
    inicio: { trazo: Home, relleno: HomeRelleno },
    seguridad: { trazo: ShieldCheck, relleno: ShieldCheckRelleno },
    mantenedores: { trazo: Folder, relleno: FolderRelleno },
    configuracion: { trazo: Settings, relleno: SettingsRelleno },
    inscripciones: { trazo: ClipboardList, relleno: ClipboardListRelleno },
    reportes: { trazo: ChartBar, relleno: ChartBarRelleno },
} satisfies Record<string, { trazo: Icono; relleno: Icono }>;

export type NombreIconoMenu = keyof typeof iconosMenu;

/**
 * Íconos del encabezado de cada página, en dos tonos. Las páginas los indican
 * por nombre en sus datos de layout.
 */
export const iconosPagina = {
    inicio: HomeDosTonos,
    usuarios: Users,
    roles: ShieldUser,
    permisos: ShieldKey,
    aulas: Building,
    carreras: GraduationCap,
    colegios: School,
    conceptos: Banknote,
    modalidades: ListChecks,
    sedes: MapPin,
    turnos: Clock,
    procesos: CalendarDays,
    oferta: ClipboardListDosTonos,
    reglasPago: CreditCard,
    codigos: FileSpreadsheet,
    carnet: IdCard,
    inscripciones: ClipboardCheck,
    fotos: Camera,
    pagos: Wallet,
    validarPagos: ShieldCheckDosTonos,
    reportes: ChartBarDosTonos,
    componentes: LayoutDashboard,
} satisfies Record<string, Icono>;

export type NombreIconoPagina = keyof typeof iconosPagina;
