@extends ('errors::layout')

@section ('code', '404')
@section ('title', 'Página no encontrada')

@section ('illustration')
    <x-ui.icon name="help-circle" size="10" />
@endsection

@section ('message')
    Lo sentimos, la página que buscas no existe.
@endsection
