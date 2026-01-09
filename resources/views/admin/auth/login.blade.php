@extends('admin.layouts.auth')

@section('title', 'Đăng nhập Admin')

@section('content')
<div class="loginwrapper">
    <div class="lg-inner-column">
        <div class="left-column relative z-[1]">
            <div class="max-w-[520px] pt-20 ltr:pl-20 rtl:pr-20">
                <a href="{{ route('admin.dashboard') }}">
                    <img src="{{ asset('dashcode/assets/images/logo/logo.svg') }}" alt="" class="mb-10 dark_logo">
                    <img src="{{ asset('dashcode/assets/images/logo/logo-white.svg') }}" alt="" class="mb-10 white_logo">
                </a>
                <h4>
                    Mở khóa hiệu suất
                    <span class="text-slate-800 dark:text-slate-400 font-bold">
                        dự án của bạn
                    </span>
                </h4>
            </div>
            <div class="absolute left-0 2xl:bottom-[-160px] bottom-[-130px] h-full w-full z-[-1]">
                <img src="{{ asset('dashcode/assets/images/auth/ils1.svg') }}" alt="" class="h-full w-full object-contain">
            </div>
        </div>
        <div class="right-column relative">
            <div class="inner-content h-full flex flex-col bg-white dark:bg-slate-800">
                <div class="auth-box h-full flex flex-col justify-center">
                    <div class="mobile-logo text-center mb-6 lg:hidden block">
                        <a href="{{ route('admin.dashboard') }}">
                            <img src="{{ asset('dashcode/assets/images/logo/logo.svg') }}" alt="" class="mb-10 dark_logo">
                            <img src="{{ asset('dashcode/assets/images/logo/logo-white.svg') }}" alt="" class="mb-10 white_logo">
                        </a>
                    </div>
                    <div class="text-center 2xl:mb-10 mb-4">
                        <h4 class="font-medium">Đăng nhập</h4>
                        <div class="text-slate-500 text-base">
                            Đăng nhập vào tài khoản của bạn để bắt đầu
                        </div>
                    </div>
                    
                    @if ($errors->any())
                        <div class="alert alert-danger-500 text-white bg-danger-500 rounded-md p-3 mb-4">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- BEGIN: Login Form -->
                    <form class="space-y-4" action="{{ route('admin.login') }}" method="POST">
                        @csrf
                        <div class="fromGroup">
                            <label class="block capitalize form-label">Tên đăng nhập</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    name="username" 
                                    class="form-control py-2 @error('username') border-danger-500 @enderror" 
                                    placeholder="Nhập tên đăng nhập" 
                                    value="{{ old('username') }}"
                                    required
                                    autofocus
                                >
                            </div>
                            @error('username')
                                <span class="text-danger-500 text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="fromGroup">
                            <label class="block capitalize form-label">Mật khẩu</label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    name="password" 
                                    class="form-control py-2 @error('password') border-danger-500 @enderror" 
                                    placeholder="Nhập mật khẩu"
                                    required
                                >
                            </div>
                            @error('password')
                                <span class="text-danger-500 text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-dark block w-full text-center">Đăng nhập</button>
                    </form>
                    <!-- END: Login Form -->
                </div>
                <div class="auth-footer text-center">
                    Copyright 2024, All Rights Reserved.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
