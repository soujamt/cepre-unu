@extends ('errors::layout')

@section ('code', '402')
@section ('title', 'Pago Requerido')

@section ('illustration')
    <i class="ki-duotone ki-lock text-7xl text-red-600"></i>
@endsection

@section ('message')
    Se requiere un pago para acceder a este recurso. Por favor, realiza el pago correspondiente o contacta a soporte
    técnico si el problema persiste.
@endsection
