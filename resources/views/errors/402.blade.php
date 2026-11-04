@extends ('errors::layout')

@section ('code', '402')
@section ('title', 'Pago Requerido')

@section ('illustration')
    <x-ui.icon name="lock" size="10" />
@endsection

@section ('message')
    Se requiere un pago para acceder a este recurso. Por favor, realiza el pago correspondiente o contacta a soporte
    técnico si el problema persiste.
@endsection
