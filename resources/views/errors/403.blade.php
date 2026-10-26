@extends('errors::minimal')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: 'Access denied'))
@section('detail', __("You don't have permission to view this page. Ask an administrator if you think this is a mistake."))
