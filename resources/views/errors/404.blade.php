@extends ('errors::layout')

@section ('code', '404')
@section ('title', 'Página no encontrada')

@section ('illustration')
    <i class="ki-duotone ki-question text-7xl text-red-600"></i>
@endsection

@section ('message')
    Lo sentimos, la página que buscas no existe.
@endsection
