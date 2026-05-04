@extends ('errors::layout')

@section ('code', '429')
@section ('title', 'Demasiadas solicitudes')

@section ('illustration')
    <i class="ki-duotone ki-timer text-7xl text-red-600"></i>
@endsection

@section ('message')
    Has realizado demasiadas solicitudes en un corto período de tiempo. Por favor, intenta nuevamente más tarde o
    contacta a soporte técnico si el problema persiste.
@endsection
