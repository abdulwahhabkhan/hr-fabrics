@extends('errors::minimal')

@section('title', __('Server Error'))
@section('code', '500')
@section('message', __('Something went wrong'))
@section('detail', __("An unexpected error occurred on our side. Please try again in a moment — if it keeps happening, let the team know."))
