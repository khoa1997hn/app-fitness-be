<!-- BEGIN: Footer For Desktop and tab -->
<footer class="md:block hidden" id="footer">
    <div class="site-footer px-6 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-300 py-4 ltr:ml-[248px] rtl:mr-[248px]">
        <div class="grid md:grid-cols-2 grid-cols-1 md:gap-5">
            <div class="text-center ltr:md:text-start rtl:md:text-right text-sm">
                COPYRIGHT ©
                <span id="thisYear"></span>
                DashCode, All rights Reserved
            </div>
            <div class="ltr:md:text-right rtl:md:text-end text-center text-sm">
                Hand-crafted &amp; Made by
                <a href="https://codeshaper.net" target="_blank" class="text-primary-500 font-semibold">
                    Codeshaper
                </a>
            </div>
        </div>
    </div>
</footer>
<!-- END: Footer For Desktop and tab -->

<!-- BEGIN: Footer For Mobile -->
<div class="bg-white bg-no-repeat custom-dropshadow footer-bg dark:bg-slate-700 flex justify-around items-center backdrop-filter backdrop-blur-[40px] fixed left-0 bottom-0 w-full z-[9999] bothrefm-0 py-[12px] px-4 md:hidden">
    <a href="{{ route('admin.dashboard') }}">
        <div>
            <span class="relative cursor-pointer rounded-full text-[20px] flex flex-col items-center justify-center mb-1 dark:text-white text-slate-900">
                <iconify-icon icon="heroicons-outline:home"></iconify-icon>
            </span>
            <span class="block text-[11px] text-slate-600 dark:text-slate-300">
                Trang chủ
            </span>
        </div>
    </a>
    <a href="{{ route('admin.dashboard') }}" class="relative bg-white bg-no-repeat backdrop-filter backdrop-blur-[40px] rounded-full footer-bg dark:bg-slate-700 h-[65px] w-[65px] z-[-1] -mt-[40px] flex justify-center items-center">
        <div class="h-[50px] w-[50px] rounded-full relative left-[0px] hrefp-[0px] custom-dropshadow">
            <img src="{{ asset('dashcode/assets/images/all-img/user.png') }}" alt="" class="w-full h-full rounded-full border-2 border-slate-100">
        </div>
    </a>
    <a href="{{ route('admin.users.index') }}">
        <div>
            <span class="relative cursor-pointer rounded-full text-[20px] flex flex-col items-center justify-center mb-1 dark:text-white text-slate-900">
                <iconify-icon icon="heroicons-outline:users"></iconify-icon>
            </span>
            <span class="block text-[11px] text-slate-600 dark:text-slate-300">
                Khách hàng
            </span>
        </div>
    </a>
</div>
<!-- END: Footer For Mobile -->

