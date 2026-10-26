@extends('errors::minimal')

@section('title', __('Too Many Requests'))
@section('code', '429')
@section('message', __('Too many requests'))
@section('detail', __("You're going a little fast. Wait a moment, then try again."))
