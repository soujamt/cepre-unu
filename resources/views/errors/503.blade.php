@extends ('errors::layout')

@section ('code', '503')
@section ('title', 'Servicio no disponible')

@section ('illustration')
    <i class="ki-duotone ki-cross-circle text-7xl text-red-600"></i>
@endsection

@section ('message')
    El servicio no está disponible en este momento. Por favor, intenta nuevamente más tarde o contacta a soporte técnico
    si el problema persiste.
@endsection
