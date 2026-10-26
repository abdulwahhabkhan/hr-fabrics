@extends('errors::minimal')

@section('title', __('Service Unavailable'))
@section('code', '503')
@section('message', __('Be right back'))
@section('detail', __("We're doing some quick maintenance. Please check back in a few minutes."))
