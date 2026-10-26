@extends('errors::minimal')

@section('title', __('Page Expired'))
@section('code', '419')
@section('message', __('Page expired'))
@section('detail', __("Your session timed out for security. Refresh the page and try again."))
