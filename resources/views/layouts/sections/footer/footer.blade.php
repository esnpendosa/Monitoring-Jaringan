@php
$containerFooter = !empty($containerNav) ? $containerNav : 'container-fluid';
@endphp

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
    <div class="{{ $containerFooter }}">
        <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
            <div class="text-body">
                © <script>
                document.write(new Date().getFullYear())
                </script>, <a href="#" class="footer-link fw-semibold">CV. Rozitech Multimedia Indonesia</a>
            </div>
            <div class="d-none d-lg-inline-block">
                <span class="footer-link me-4">Sistem Manajemen Jaringan WiFi</span>
            </div>
        </div>
    </div>
</footer>
<!--/ Footer-->