@extends('admin.layouts.app')

@section('title', 'Bảng điều khiển')

@section('content')
<div class="grid grid-cols-12 gap-5">
    <div class="col-span-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Chào mừng đến với Bảng quản trị</h4>
            </div>
            <div class="card-body px-6 pb-6">
                <p>Bạn đang đăng nhập với tên: <strong>{{ Auth::guard('admin')->user()->name }}</strong></p>
                <p class="mt-2">Tên đăng nhập: <strong>{{ Auth::guard('admin')->user()->username }}</strong></p>
            </div>
        </div>
    </div>
</div>
@endsection
