@extends('errors::minimal')

@section('title', __('Payment Required'))
@section('code', '402')
@section('message', __('Payment Required'))
@section('detail', __("This feature needs an active subscription to continue."))
