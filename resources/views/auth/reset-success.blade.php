<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <!-- Validation Errors -->
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

 

            <div class="text-center justify-center mt-4">
                
                <h2 class="text-green-700 text-2xl font-bold mt-4">!Bien¡</h2>
                <h3 class="text-green-700 text-lg mt-4">Vuelva a la App para ingresar con sus nuevas credenciales</h3>
                


            </div>
        
    </x-auth-card>
</x-guest-layout>
