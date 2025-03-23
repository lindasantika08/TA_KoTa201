@component('mail::message')
# Informasi Akun 

Halo {{ $nama }},

Berikut adalah informasi akun Anda untuk mengakses sistem:

**Email:** {{ $email }}  
**Password:** {{ $password }}

Silakan login menggunakan Email dan Password di atas. Demi keamanan, segera ubah password Anda di aplikasi setelah berhasil login.

Terima kasih,
@endcomponent