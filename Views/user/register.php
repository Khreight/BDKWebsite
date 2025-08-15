<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-100 to-blue-100 py-8">
    <div class="w-full max-w-3xl bg-white rounded-xl shadow-lg p-8">
        <div class="flex flex-col items-center mb-6">
            <img src="Assets/Logo/logo.png" alt="BDK Karting Logo" class="h-14 mb-1">
            <h2 class="text-2xl font-extrabold text-blue-700 mb-1">Join BDK Karting!</h2>
            <p class="text-gray-600 text-center text-sm">Register below to become a member and start racing with us.</p>
        </div>
        <form action="/register" method="post">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label for="first_name" class="block text-gray-700 font-semibold mb-1 text-sm">First Name</label>
                    <input type="text" id="first_name" name="first_name" required autocomplete="given-name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition text-sm">
                </div>
                <div>
                    <label for="last_name" class="block text-gray-700 font-semibold mb-1 text-sm">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required autocomplete="family-name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition text-sm">
                </div>
                <div>
                    <label for="email" class="block text-gray-700 font-semibold mb-1 text-sm">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition text-sm">
                </div>
                <div>
                    <label for="phone" class="block text-gray-700 font-semibold mb-1 text-sm">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required autocomplete="tel"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition text-sm">
                </div>
                <div>
                    <label for="birthdate" class="block text-gray-700 font-semibold mb-1 text-sm">Date of Birth</label>
                    <input type="date" id="birthdate" name="birthdate" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition text-sm">
                </div>
                <div>
                    <label for="nationality" class="block text-gray-700 font-semibold mb-1 text-sm">Nationality</label>
                    <select id="nationality" name="nationality" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition text-sm">
                        <option value="">Select your nationality</option>
                        <option value="French">French</option>
                        <option value="British">British</option>
                        <option value="German">German</option>
                        <option value="Italian">Italian</option>
                        <option value="Spanish">Spanish</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label for="city" class="block text-gray-700 font-semibold mb-1 text-sm">City</label>
                    <input type="text" id="city" name="city" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition text-sm">
                </div>
            </div>
            <div class="mt-6">
                <button type="submit"
                    class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 rounded-lg shadow transition text-base tracking-wide">
                    Register Now
                </button>
            </div>
        </form>
        <p class="text-center text-gray-500 text-xs mt-4">
            Already have an account?
            <a href="/login" class="text-blue-600 hover:underline font-semibold">Sign in</a>
        </p>
    </div>
</div>
