@if (session('success') || session('error') || session('info') || session('warning'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const notyf = new Notyf({
                duration: 5000,
                position: {
                    x: 'right',
                    y: 'bottom',
                },
                dismissible: true,
                ripple: false,
                className: 'notyf-custom',
            });

            @if(session('success'))
                notyf.success(@json(session('success')));
            @endif

            @if(session('error'))
                notyf.error(@json(session('error')));
            @endif

            @if(session('info'))
                notyf.open({
                    type: 'info',
                    message: @json(session('info')),
                    background: '#198bce',
                });
            @endif

            @if(session('warning'))
                notyf.open({
                    type: 'warning',
                    message: @json(session('warning')),
                    background: '#d97706',
                });
            @endif

        });
    </script>
@endif