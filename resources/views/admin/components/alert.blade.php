@if(session('success') || session('error') || session('warning') || session('info'))
    <div class="fixed top-20 right-4 z-[9999] space-y-3 max-w-md w-full md:max-w-md sm:max-w-sm px-4 md:px-0" id="alert-container">
        @if(session('success'))
            <div class="alert-item py-[18px] px-6 font-normal text-sm rounded-md bg-success-500 text-white shadow-lg animate-slide-in-right">
                <div class="flex items-center space-x-3 rtl:space-x-reverse">
                    <iconify-icon class="text-2xl flex-0" icon="heroicons-outline:check-circle"></iconify-icon>
                    <p class="flex-1 font-Inter">
                        {{ session('success') }}
                    </p>
                    <div class="flex-0 text-xl cursor-pointer alert-close">
                        <iconify-icon icon="line-md:close"></iconify-icon>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="alert-item py-[18px] px-6 font-normal text-sm rounded-md bg-danger-500 text-white shadow-lg animate-slide-in-right">
                <div class="flex items-center space-x-3 rtl:space-x-reverse">
                    <iconify-icon class="text-2xl flex-0" icon="heroicons-outline:x-circle"></iconify-icon>
                    <p class="flex-1 font-Inter">
                        {{ session('error') }}
                    </p>
                    <div class="flex-0 text-xl cursor-pointer alert-close">
                        <iconify-icon icon="line-md:close"></iconify-icon>
                    </div>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert-item py-[18px] px-6 font-normal text-sm rounded-md bg-warning-500 text-white shadow-lg animate-slide-in-right">
                <div class="flex items-center space-x-3 rtl:space-x-reverse">
                    <iconify-icon class="text-2xl flex-0" icon="heroicons-outline:exclamation-triangle"></iconify-icon>
                    <p class="flex-1 font-Inter">
                        {{ session('warning') }}
                    </p>
                    <div class="flex-0 text-xl cursor-pointer alert-close">
                        <iconify-icon icon="line-md:close"></iconify-icon>
                    </div>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="alert-item py-[18px] px-6 font-normal text-sm rounded-md bg-info-500 text-white shadow-lg animate-slide-in-right">
                <div class="flex items-center space-x-3 rtl:space-x-reverse">
                    <iconify-icon class="text-2xl flex-0" icon="heroicons-outline:information-circle"></iconify-icon>
                    <p class="flex-1 font-Inter">
                        {{ session('info') }}
                    </p>
                    <div class="flex-0 text-xl cursor-pointer alert-close">
                        <iconify-icon icon="line-md:close"></iconify-icon>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        @keyframes slide-in-right {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .animate-slide-in-right {
            animation: slide-in-right 0.3s ease-out;
        }

        @keyframes slide-out-right {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .animate-slide-out-right {
            animation: slide-out-right 0.3s ease-out;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alertItems = document.querySelectorAll('.alert-item');
            
            // Auto hide after 5 seconds
            alertItems.forEach(function(alert) {
                setTimeout(function() {
                    if (alert && alert.parentNode) {
                        alert.classList.add('animate-slide-out-right');
                        setTimeout(function() {
                            if (alert && alert.parentNode) {
                                alert.remove();
                                // Remove container if empty
                                const container = document.getElementById('alert-container');
                                if (container && container.children.length === 0) {
                                    container.remove();
                                }
                            }
                        }, 300);
                    }
                }, 5000);
            });

            // Close button handler
            document.querySelectorAll('.alert-close').forEach(function(closeBtn) {
                closeBtn.addEventListener('click', function() {
                    const alert = this.closest('.alert-item');
                    if (alert) {
                        alert.classList.add('animate-slide-out-right');
                        setTimeout(function() {
                            if (alert && alert.parentNode) {
                                alert.remove();
                                // Remove container if empty
                                const container = document.getElementById('alert-container');
                                if (container && container.children.length === 0) {
                                    container.remove();
                                }
                            }
                        }, 300);
                    }
                });
            });
        });
    </script>
@endif

