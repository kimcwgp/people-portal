<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\LoginLinkMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{DB, Mail, Log, Auth, Hash};
use Illuminate\Http\JsonResponse;
use App\Services\RingCentralService;

class AuthController extends Controller
{
    private RingCentralService $ringCentral;

    public function __construct()
    {
        $this->ringCentral = new RingCentralService();
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $user = User::with('roles.permissions', 'permissions')
                ->where('email', $credentials['email'])
                ->first();

            if (!$user || !$user->password || !Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'These credentials do not match our records.'
                ], 401);
            }

            if (!$user->status) {
                return response()->json([
                    'success' => false,
                    'message' => 'This account has been deactivated. Please contact HR.'
                ], 403);
            }

            $apiToken = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $apiToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ], 500);
        }
    }

    public function sendLoginLink(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this email address.'
                ], 404);
            }

            $glipUrl = $user->glip_url;
            $token = hash('sha256', Str::random(60) . $user->id . microtime());
            DB::table('login_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => $token,
                    'updated_at' => now(),
                    'created_at' => DB::raw('IFNULL(created_at, NOW())')
                ]
            );

            $loginLink = config('app.url') . '/auto_login/' . $token;

            // Convenience for local development: writes the link as one
            // greppable line so it can be copied straight out of the log
            // instead of being dug out of the rendered email body.
            // Never enabled in production — the link is a working credential.
            if (! app()->isProduction()) {
                Log::info('LOGIN LINK for ' . $request->email . ' => ' . $loginLink);
            }

            Mail::to($request->email)->send(new LoginLinkMail($loginLink, $user->name));
            if ($glipUrl) {
                $this->ringCentral->sendLoginNotification($request->email, $loginLink, $glipUrl);
            }

            return response()->json([
                'success' => true,
                'message' => 'Login link sent to your email!'
            ]);

        } catch (\Exception $e) {
            Log::error('Login link error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send login link: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verifyLoginLink(string $token): JsonResponse
    {
        try {
            $tokenRecord = DB::table('login_tokens')
                ->where('token', $token)
                ->where('created_at', '>', now()->subMinutes(15))
                ->first();

            if (!$tokenRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired link'
                ], 401);
            }

            // Eager load roles and permissions to avoid N+1
            $user = User::with('roles.permissions', 'permissions')
                ->where('email', $tokenRecord->email)
                ->first();

            $apiToken = $user->createToken('auth-token')->plainTextToken;
            DB::table('login_tokens')->where('token', $token)->delete();

            return response()->json([
                'success' => true,
                'token' => $apiToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name')
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Verify login link error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
