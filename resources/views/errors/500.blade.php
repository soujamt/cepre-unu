@extends ('errors::layout')

@section ('code', '500')
@section ('title', 'Error del servidor')

@section ('illustration')
    <i class="ki-duotone ki-information-1 text-7xl text-red-600"></i>
@endsection

@section ('message')
    Ha ocurrido un error interno en el servidor. Por favor, intenta nuevamente más tarde o contacta a soporte técnico si
    el problema persiste.
@endsection
