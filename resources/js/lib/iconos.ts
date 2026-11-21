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
    School,
    ShieldKey,
    ShieldUser,
    UserPlus,
    Users,
    Wallet,
} from '@keyline-icons/react';
import {
    BadgeCheck as BadgeCheckRelleno,
    Banknote as BanknoteRelleno,
    Building as BuildingRelleno,
    CalendarDays as CalendarDaysRelleno,
    Camera as CameraRelleno,
    ChartBar as ChartBarRelleno,
    ClipboardCheck as ClipboardCheckRelleno,
    ClipboardList as ClipboardListRelleno,
    Clock as ClockRelleno,
    CreditCard as CreditCardRelleno,
    FileSpreadsheet as FileSpreadsheetRelleno,
    FileText as FileTextRelleno,
    GraduationCap as GraduationCapRelleno,
    Home as HomeRelleno,
    IdCard as IdCardRelleno,
    LayoutDashboard as LayoutDashboardRelleno,
    Layers as LayersRelleno,
    ListChecks as ListChecksRelleno,
    MapPin as MapPinRelleno,
    School as SchoolRelleno,
    ShieldKey as ShieldKeyRelleno,
    ShieldUser as ShieldUserRelleno,
    UserPlus as UserPlusRelleno,
    Users as UsersRelleno,
    Wallet as WalletRelleno,
} from '@keyline-icons/react/fill';
import type { ComponentType } from 'react';

export type Icono = ComponentType<IconProps>;

/**
 * Íconos de cada pantalla: el de trazo para el menú y el relleno para el ítem activo
 * y para el recuadro del encabezado. Las páginas los indican por nombre en su layout.
 */
export const iconos = {
    inicio: { trazo: Home, relleno: HomeRelleno },
    usuarios: { trazo: Users, relleno: UsersRelleno },
    roles: { trazo: ShieldUser, relleno: ShieldUserRelleno },
    permisos: { trazo: ShieldKey, relleno: ShieldKeyRelleno },
    aulas: { trazo: Building, relleno: BuildingRelleno },
    carreras: { trazo: GraduationCap, relleno: GraduationCapRelleno },
    colegios: { trazo: School, relleno: SchoolRelleno },
    conceptos: { trazo: Banknote, relleno: BanknoteRelleno },
    modalidades: { trazo: ListChecks, relleno: ListChecksRelleno },
    sedes: { trazo: MapPin, relleno: MapPinRelleno },
    turnos: { trazo: Clock, relleno: ClockRelleno },
    procesos: { trazo: CalendarDays, relleno: CalendarDaysRelleno },
    aulasProceso: { trazo: Layers, relleno: LayersRelleno },
    oferta: { trazo: ClipboardList, relleno: ClipboardListRelleno },
    reglasPago: { trazo: CreditCard, relleno: CreditCardRelleno },
    codigos: { trazo: FileSpreadsheet, relleno: FileSpreadsheetRelleno },
    carnet: { trazo: IdCard, relleno: IdCardRelleno },
    inscripciones: { trazo: ClipboardCheck, relleno: ClipboardCheckRelleno },
    nuevaInscripcion: { trazo: UserPlus, relleno: UserPlusRelleno },
    fotos: { trazo: Camera, relleno: CameraRelleno },
    pagos: { trazo: Wallet, relleno: WalletRelleno },
    validarPagos: { trazo: BadgeCheck, relleno: BadgeCheckRelleno },
    reportes: { trazo: ChartBar, relleno: ChartBarRelleno },
    detalle: { trazo: FileText, relleno: FileTextRelleno },
    componentes: { trazo: LayoutDashboard, relleno: LayoutDashboardRelleno },
} satisfies Record<string, { trazo: Icono; relleno: Icono }>;

export type NombreIcono = keyof typeof iconos;

