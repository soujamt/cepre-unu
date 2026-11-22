import type { IconProps } from '@keyline-icons/react';
import {
    BadgeCheck,
    Banknote,
    Building,
    CalendarDays,
    Camera,
    ChartBar,
    ClipboardCheck,
    ClipboardList,
    Clock,
    CreditCard,
    FileSpreadsheet,
    FileText,
    GraduationCap,
    Home,
    IdCard,
    LayoutDashboard,
    Layers,
    ListChecks,
    MapPin,
    Repeat,
    School,
    ShieldKey,
    ShieldUser,
    UserPlus,
    Users,
    Wallet,
} from '@keyline-icons/react/fill';
import type { ComponentType } from 'react';

export type Icono = ComponentType<IconProps>;

/**
 * Ícono (relleno) de cada pantalla, para el menú, el encabezado y los accesos del
 * inicio. Las páginas lo indican por nombre en su layout.
 */
export const iconos = {
    inicio: Home,
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
    aulasProceso: Layers,
    oferta: ClipboardList,
    reglasPago: CreditCard,
    codigos: FileSpreadsheet,
    carnet: IdCard,
    inscripciones: ClipboardCheck,
    nuevaInscripcion: UserPlus,
    fotos: Camera,
    pagos: Wallet,
    validarPagos: BadgeCheck,
    reportes: ChartBar,
    detalle: FileText,
    editarInscripcion: Repeat,
    componentes: LayoutDashboard,
} satisfies Record<string, Icono>;

export type NombreIcono = keyof typeof iconos;
