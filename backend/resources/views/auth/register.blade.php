@extends('layouts.app')

@section('meta_title', 'Create Account | '.($siteSettings['site_name'] ?? 'Estele'))
@section('meta_description', 'Create an account to track your orders and check out faster.')

@section('content')

<div class="bg-ivory py-10 md:py-16 min-h-[calc(100vh-220px)]">
    <div class="mx-auto w-full max-w-[450px] px-4">

        <div class="rounded-2xl border border-line bg-white p-6 sm:p-8 shadow-md">

            {{-- Heading --}}
            <h1 class="font-serif text-[24px] font-semibold text-heading text-center mb-1">
                Create Your Account
            </h1>

            <p class="text-[13px] text-muted text-center mb-6">
                Join Estele for exclusive member offers &amp; faster checkout.
            </p>


            {{-- Success message --}}
            @if(session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-[13px] text-green-700">
                    {{ session('success') }}
                </div>
            @endif


            {{-- General validation error --}}
            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-700">
                    Please correct the highlighted fields below.
                </div>
            @endif


            {{-- Registration Form --}}
            <form
                action="{{ route('register.attempt') }}"
                method="POST"
                class="space-y-4"
                id="registration-form"
            >

                @csrf


                {{-- Full Name --}}
                <div>
                    <label
                        class="mb-1.5 block text-[13px] font-medium text-heading"
                        for="name"
                    >
                        Full Name
                    </label>

                    <input
                        class="h-12 w-full rounded-lg border border-line-strong bg-white px-4 text-[14px] outline-none transition-colors placeholder:text-muted focus:border-accent focus:ring-1 focus:ring-accent"
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        placeholder="First and last name"
                        required
                        autofocus
                    >

                    @error('name')
                        <p class="mt-1 text-[12px] text-salebadge">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- Email --}}
                <div>
                    <label
                        class="mb-1.5 block text-[13px] font-medium text-heading"
                        for="email"
                    >
                        Email Address
                    </label>

                    <input
                        class="h-12 w-full rounded-lg border border-line-strong bg-white px-4 text-[14px] outline-none transition-colors placeholder:text-muted focus:border-accent focus:ring-1 focus:ring-accent"
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        placeholder="name@example.com"
                        required
                    >

                    @error('email')
                        <p class="mt-1 text-[12px] text-salebadge">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- Mobile Number --}}
                <div>

                    <label
                        class="mb-1.5 block text-[13px] font-medium text-heading"
                        for="phone"
                    >
                        Mobile Number
                    </label>

                    <div class="flex">

                        <span class="inline-flex h-12 items-center rounded-l-lg border border-r-0 border-line-strong bg-warmbeige/30 px-3.5 text-[13px] font-semibold text-heading">
                            +91
                        </span>

                        <input
                            class="h-12 w-full rounded-r-lg border border-line-strong bg-white px-4 text-[14px] outline-none transition-colors placeholder:text-muted focus:border-accent focus:ring-1 focus:ring-accent"
                            id="phone"
                            name="phone"
                            type="tel"
                            value="{{ old('phone', $prefillPhone) }}"
                            placeholder="10-digit mobile number"
                            inputmode="numeric"
                            pattern="[0-9]{10}"
                            maxlength="10"
                            required
                        >

                    </div>

                    @error('phone')
                        <p class="mt-1 text-[12px] text-salebadge">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Send OTP --}}
                <div>

                    <button
                        type="button"
                        id="send-registration-otp"
                        class="h-12 w-full rounded-lg border border-accent bg-white text-[13px] font-semibold uppercase tracking-[0.6px] text-accent shadow-sm transition-colors hover:bg-accent hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Send OTP
                    </button>

                    <p
                        id="otp-send-message"
                        class="mt-2 hidden text-center text-[12px]"
                    ></p>

                </div>


                {{-- OTP Section --}}
                <div
                    id="otp-section"
                    class="hidden rounded-lg border border-line bg-ivory/40 p-4"
                >

                    <label
                        class="mb-1.5 block text-[13px] font-medium text-heading"
                        for="registration_otp"
                    >
                        Enter OTP
                    </label>

                    <input
                        class="h-12 w-full rounded-lg border border-line-strong bg-white px-4 text-center text-[20px] font-semibold tracking-[8px] outline-none transition-colors placeholder:text-muted focus:border-accent focus:ring-1 focus:ring-accent"
                        id="registration_otp"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        placeholder="••••••"
                        autocomplete="one-time-code"
                    >

                    <button
                        type="button"
                        id="verify-registration-otp"
                        class="mt-3 h-12 w-full rounded-lg bg-accent text-[13px] font-semibold uppercase tracking-[0.6px] text-white shadow-sm transition-colors hover:bg-accent-dark disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Verify OTP
                    </button>

                    <p
                        id="otp-verify-message"
                        class="mt-2 hidden text-center text-[12px]"
                    ></p>

                </div>


                {{-- Password (optional) --}}
                <div>

                    <label
                        class="mb-1.5 block text-[13px] font-medium text-heading"
                        for="password"
                    >
                        Password (Optional)
                    </label>

                    <input
                        class="h-12 w-full rounded-lg border border-line-strong bg-white px-4 text-[14px] outline-none transition-colors placeholder:text-muted focus:border-accent focus:ring-1 focus:ring-accent"
                        id="password"
                        name="password"
                        type="password"
                        placeholder="At least 8 characters"
                        minlength="8"
                        autocomplete="new-password"
                    >

                    <p class="mt-1 text-[12px] text-muted">
                        Set a password to also log in with email. You can always log in with mobile OTP.
                    </p>

                    @error('password')
                        <p class="mt-1 text-[12px] text-salebadge">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Confirm Password --}}
                <div>

                    <label
                        class="mb-1.5 block text-[13px] font-medium text-heading"
                        for="password_confirmation"
                    >
                        Confirm Password
                    </label>

                    <input
                        class="h-12 w-full rounded-lg border border-line-strong bg-white px-4 text-[14px] outline-none transition-colors placeholder:text-muted focus:border-accent focus:ring-1 focus:ring-accent"
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        placeholder="Re-enter your password"
                        minlength="8"
                        autocomplete="new-password"
                    >

                </div>


                {{-- Create Account --}}
                <button
                    id="create-account-button"
                    type="submit"
                    disabled
                    class="mt-2 h-12 w-full rounded-lg bg-accent text-[13px] font-semibold uppercase tracking-[0.6px] text-white shadow-sm transition-colors hover:bg-accent-dark disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Create Account
                </button>

            </form>


            {{-- Login link --}}
            <p class="mt-6 text-center text-[13px] text-muted">

                Already have an account?

                <a
                    class="font-semibold text-accent underline hover:text-accent-dark"
                    href="{{ route('login') }}"
                >
                    Log in
                </a>

            </p>

        </div>

    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const phoneInput = document.getElementById('phone');

    const sendOtpButton =
        document.getElementById('send-registration-otp');

    const otpSection =
        document.getElementById('otp-section');

    const otpInput =
        document.getElementById('registration_otp');

    const verifyOtpButton =
        document.getElementById('verify-registration-otp');

    const createAccountButton =
        document.getElementById('create-account-button');

    const otpSendMessage =
        document.getElementById('otp-send-message');

    const otpVerifyMessage =
        document.getElementById('otp-verify-message');


    /*
    |--------------------------------------------------------------------------
    | Send OTP
    |--------------------------------------------------------------------------
    */

    sendOtpButton.addEventListener('click', async function () {

        const phone = phoneInput.value.trim();

        if (!/^\d{10}$/.test(phone)) {

            otpSendMessage.textContent =
                'Please enter a valid 10-digit mobile number.';

            otpSendMessage.className =
                'mt-2 text-center text-[12px] text-red-600';

            otpSendMessage.classList.remove('hidden');

            phoneInput.focus();

            return;
        }


        sendOtpButton.disabled = true;

        sendOtpButton.textContent =
            'Sending OTP...';


        try {

            const response = await fetch(
                '{{ route('register.otp.send') }}',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },

                    credentials: 'same-origin',

                    body: JSON.stringify({
                        phone: phone
                    })
                }
            );


            const data = await response.json();


            if (!response.ok) {

                let message =
                    'Unable to send OTP.';

                if (data.message) {
                    message = data.message;
                }

                if (
                    data.errors &&
                    data.errors.phone
                ) {
                    message = data.errors.phone[0];
                }

                throw new Error(message);
            }


            otpSection.classList.remove('hidden');

            otpSendMessage.textContent =
                'OTP sent successfully. Please check your mobile.';

            otpSendMessage.className =
                'mt-2 text-center text-[12px] text-green-600';

            otpSendMessage.classList.remove('hidden');

            sendOtpButton.textContent =
                'Resend OTP';

            otpInput.focus();

        } catch (error) {

            otpSendMessage.textContent =
                error.message;

            otpSendMessage.className =
                'mt-2 text-center text-[12px] text-red-600';

            otpSendMessage.classList.remove('hidden');

            sendOtpButton.textContent =
                'Send OTP';

        } finally {

            sendOtpButton.disabled = false;

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Verify OTP
    |--------------------------------------------------------------------------
    */

    verifyOtpButton.addEventListener('click', async function () {

        const code = otpInput.value.trim();


        if (!/^\d{6}$/.test(code)) {

            otpVerifyMessage.textContent =
                'Please enter the 6-digit OTP.';

            otpVerifyMessage.className =
                'mt-2 text-center text-[12px] text-red-600';

            otpVerifyMessage.classList.remove('hidden');

            otpInput.focus();

            return;
        }


        verifyOtpButton.disabled = true;

        verifyOtpButton.textContent =
            'Verifying...';


        try {

            const response = await fetch(
                '{{ route('register.otp.verify') }}',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },

                    credentials: 'same-origin',

                    body: JSON.stringify({
                        code: code
                    })
                }
            );


            const data = await response.json();


            if (!response.ok) {

                let message =
                    'Invalid or expired OTP.';

                if (data.message) {
                    message = data.message;
                }

                if (
                    data.errors &&
                    data.errors.code
                ) {
                    message = data.errors.code[0];
                }

                throw new Error(message);
            }


            otpVerifyMessage.textContent =
                'Mobile number verified successfully.';

            otpVerifyMessage.className =
                'mt-2 text-center text-[12px] text-green-600';

            otpVerifyMessage.classList.remove('hidden');


            otpInput.disabled = true;

            verifyOtpButton.disabled = true;

            verifyOtpButton.textContent =
                'OTP Verified';

            sendOtpButton.disabled = true;

            phoneInput.readOnly = true;


            // Allow account creation only after successful OTP verification.
            createAccountButton.disabled = false;

        } catch (error) {

            otpVerifyMessage.textContent =
                error.message;

            otpVerifyMessage.className =
                'mt-2 text-center text-[12px] text-red-600';

            otpVerifyMessage.classList.remove('hidden');

            verifyOtpButton.disabled = false;

            verifyOtpButton.textContent =
                'Verify OTP';

        }

    });

});
</script>

