<style>
    .btn-nav:hover {
        color: #f7f7f7; /* Couleur du texte au survol */
    }
</style>

    <nav class="bg-green-900 fixed w-full z-10" x-data="{ isOpen: false }">
        <div class="container mx-auto flex justify-between items-center py-4">
            <a class="navbar-brand flex items-center" href="/">
                <img src="{{ asset('/build/images/logo1.svg') }}" alt="Logo" class="h-8">
            </a>
            <button @click="isOpen = !isOpen" class="lg:hidden text-white focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-6 w-6 fill-current">
                    <path fill-rule="evenodd" d="M4 6h16a1 1 0 0 1 0 2H4a1 1 0 1 1 0-2zm0 5h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2zm0 5h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2z"></path>
                </svg>
            </button>
            <div class="hidden lg:flex lg:items-center lg:w-auto">
                <a href="/" class="text-white hover:text-gray-300 px-3 py-2">Home</a>
                <a href="{{ route('login') }}" class="text-white hover:text-gray-300 px-3 py-2">Login</a>
                <a href="{{ route('register') }}" class="text-white hover:text-gray-300 px-3 py-2">Register</a>
            </div>
        </div>
        <div class="lg:hidden" x-show="isOpen" @click.away="isOpen = false">
            <a href="/" class="block text-white hover:bg-green-800 px-3 py-2">Home</a>
            <a href="{{ route('login') }}" class="block text-white hover:bg-green-800 px-3 py-2">Login</a>
            <a href="{{ route('register') }}" class="block text-white hover:bg-green-800 px-3 py-2">Register</a>
        </div>
    </nav>

    <script>
        function toggleDropdown() {
            var dropdownMenu = document.getElementById("moreDropdown");
            dropdownMenu.classList.toggle("hidden");
        }

        function toggleMenu() {
            var dropdownMenu = document.getElementById("mobile-menu");
            dropdownMenu.classList.toggle("hidden");
        }
    </script>
