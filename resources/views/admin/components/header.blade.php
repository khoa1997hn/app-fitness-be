<!-- BEGIN: Header -->
<div class="z-[9]" id="app_header">
    <div class="app-header z-[999] ltr:ml-[248px] rtl:mr-[248px] bg-white dark:bg-slate-800 shadow-sm dark:shadow-slate-700">
        <div class="flex justify-between items-center h-full">
            <div class="flex items-center md:space-x-4 space-x-2 xl:space-x-0 rtl:space-x-reverse vertical-box">
                <a href="{{ route('admin.dashboard') }}" class="mobile-logo xl:hidden inline-block">
                    <img src="{{ asset('dashcode/assets/images/logo/logo-c.svg') }}" class="black_logo" alt="logo">
                    <img src="{{ asset('dashcode/assets/images/logo/logo-c-white.svg') }}" class="white_logo" alt="logo">
                </a>
                <button class="smallDeviceMenuController hidden md:inline-block xl:hidden">
                    <iconify-icon class="leading-none bg-transparent relative text-xl top-[2px] text-slate-900 dark:text-white" icon="heroicons-outline:menu-alt-3"></iconify-icon>
                </button>
            </div>
            
            <div class="items-center space-x-4 rtl:space-x-reverse horizental-box">
                <a href="{{ route('admin.dashboard') }}">
                    <span class="xl:inline-block hidden">
                        <img src="{{ asset('dashcode/assets/images/logo/logo.svg') }}" class="black_logo" alt="logo">
                        <img src="{{ asset('dashcode/assets/images/logo/logo-white.svg') }}" class="white_logo" alt="logo">
                    </span>
                    <span class="xl:hidden inline-block">
                        <img src="{{ asset('dashcode/assets/images/logo/logo-c.svg') }}" class="black_logo" alt="logo">
                        <img src="{{ asset('dashcode/assets/images/logo/logo-c-white.svg') }}" class="white_logo" alt="logo">
                    </span>
                </a>
                <button class="smallDeviceMenuController open-sdiebar-controller xl:hidden inline-block">
                    <iconify-icon class="leading-none bg-transparent relative text-xl top-[2px] text-slate-900 dark:text-white" icon="heroicons-outline:menu-alt-3"></iconify-icon>
                </button>
            </div>

            <div class="main-menu">
                <ul>
                    <!-- Menu items can be added here -->
                </ul>
            </div>

            <div class="nav-tools flex items-center lg:space-x-5 space-x-3 rtl:space-x-reverse leading-0">
                <!-- BEGIN: Profile Dropdown -->
                <div class="md:block hidden">
                    <button class="text-slate-800 dark:text-white focus:ring-0 focus:outline-none font-medium rounded-lg text-sm text-center inline-flex items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="lg:h-8 lg:w-8 h-7 w-7 rounded-full flex-1 ltr:mr-[10px] rtl:ml-[10px]">
                            <img src="{{ asset('dashcode/assets/images/all-img/user.png') }}" alt="user" class="block w-full h-full object-cover rounded-full">
                        </div>
                        <span class="flex-none text-slate-600 dark:text-white text-sm font-normal items-center lg:flex hidden overflow-hidden text-ellipsis whitespace-nowrap">{{ Auth::guard('admin')->user()->name }}</span>
                        <svg class="w-[16px] h-[16px] dark:text-white hidden lg:inline-block text-base inline-block ml-[10px] rtl:mr-[10px]" aria-hidden="true" fill="none" stroke="currentColor" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <!-- Dropdown menu -->
                    <div class="dropdown-menu z-10 hidden bg-white divide-y divide-slate-100 shadow w-44 dark:bg-slate-800 border dark:border-slate-700 !top-[23px] rounded-md overflow-hidden">
                        <ul class="py-1 text-sm text-slate-800 dark:text-slate-200">
                            <li>
                                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600 dark:hover:text-white font-inter text-sm text-slate-600 dark:text-white font-normal">
                                    <iconify-icon icon="heroicons-outline:user" class="relative top-[2px] text-lg ltr:mr-1 rtl:ml-1"></iconify-icon>
                                    <span class="font-Inter">Hồ sơ</span>
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('admin.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600 dark:hover:text-white font-inter text-sm text-slate-600 dark:text-white font-normal">
                                        <iconify-icon icon="heroicons-outline:login" class="relative top-[2px] text-lg ltr:mr-1 rtl:ml-1"></iconify-icon>
                                        <span class="font-Inter">Đăng xuất</span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- END: Profile Dropdown -->
                <!-- Mobile menu button -->
                <button class="smallDeviceMenuController md:hidden block leading-0" type="button">
                    <iconify-icon class="cursor-pointer text-slate-900 dark:text-white text-2xl" icon="heroicons-outline:menu-alt-3"></iconify-icon>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- END: Header -->
