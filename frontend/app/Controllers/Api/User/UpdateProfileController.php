<?php

namespace App\Controllers\Api\User;

use App\Libraries\JWTLibrary;
use App\Libraries\ResponseLibrary;
use App\Models\UserModel;
use CodeIgniter\Controller;

/**
 * ─────────────────────────────────────────────
 *  Update Profile API
 * ─────────────────────────────────────────────
 *  POST /api/user/update-profile
 *
 *  Header:
 *    Authorization: Bearer <token>
 *
 *  Supports two modes:
 *
 *  1) JSON body  → update text fields
 *     { "name", "username", "email", "bio",
 *       "phone", "address", "occupation" }
 *
 *  2) multipart/form-data → upload photo + text fields
 *     form field: photo (file)  + any text fields above
 * ─────────────────────────────────────────────
 */
class UpdateProfileController extends Controller
{
    private UserModel       $users;
    private ResponseLibrary $respond;
    private JWTLibrary      $jwt;

    public function __construct()
    {
        $this->users   = new UserModel();
        $this->respond = new ResponseLibrary();
        $this->jwt     = new JWTLibrary();
    }

    public function update(): \CodeIgniter\HTTP\Response
    {
        // ── 1. Authenticate via token ────────────
        $authHeader = $this->request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->respond->unauthorized('Token required.');
        }

        $token  = trim(substr($authHeader, 7));
        $result = $this->jwt->validate($token);

        if (!$result['valid']) {
            return $this->respond->unauthorized('Invalid token: ' . $result['error']);
        }

        // Verify token still in DB
        $authUser = $this->users->findByToken($token);
        if (!$authUser) {
            return $this->respond->unauthorized('Token revoked or user not found.');
        }

        $userId = $authUser['id'];

        // ── 2. Determine if JSON or multipart ────
        $contentType = $this->request->getHeaderLine('Content-Type');
        $isMultipart = str_contains($contentType, 'multipart/form-data');

        if ($isMultipart) {
            $data = $this->request->getPost();
        } else {
            $data = $this->request->getJSON(true) ?? [];
        }

        // ── 3. Nothing sent at all ───────────────
        $allowedFields = ['name', 'username', 'email', 'bio', 'phone', 'address', 'occupation', 'photo'];
        $updates       = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $updates[$field] = trim($data[$field]);
            }
        }

        // ── 4. Validate email if changing ────────
        if (isset($updates['email'])) {
            if (!filter_var($updates['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->respond->error('Invalid email format.', 422);
            }
            // Check not taken by another user
            $existing = $this->users
                ->where('email', $updates['email'])
                ->where('id !=', $userId)
                ->first();
            if ($existing) {
                return $this->respond->error('Email already taken by another account.', 409);
            }
        }

        // ── 5. Validate username if changing ─────
        if (isset($updates['username'])) {
            if (strlen($updates['username']) < 3) {
                return $this->respond->error('Username must be at least 3 characters.', 422);
            }
            if (!preg_match('/^[a-zA-Z0-9_\.]+$/', $updates['username'])) {
                return $this->respond->error('Username can only contain letters, numbers, _ and .', 422);
            }
            // Check not taken
            $existing = $this->users
                ->where('username', $updates['username'])
                ->where('id !=', $userId)
                ->first();
            if ($existing) {
                return $this->respond->error('Username already taken.', 409);
            }
        }

        // ── 6. Handle photo upload ───────────────
        $photoUrl = null;

        if ($isMultipart) {
            $photo = $this->request->getFile('photo');

            if ($photo && $photo->isValid() && !$photo->hasMoved()) {

                // Validate type
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!in_array($photo->getMimeType(), $allowedMimes)) {
                    return $this->respond->error('Photo must be JPEG, PNG, WEBP or GIF.', 422);
                }

                // Validate size (max 5MB)
                if ($photo->getSize() > 5 * 1024 * 1024) {
                    return $this->respond->error('Photo must be under 5MB.', 422);
                }

                // Delete old photo
                $current = $this->users->find($userId);
                if ($current['photo'] && file_exists(FCPATH . 'uploads/' . $current['photo'])) {
                    @unlink(FCPATH . 'uploads/' . $current['photo']);
                }

                // Save new photo to public/uploads/photos/
                $uploadDir = FCPATH . 'uploads/photos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newName = bin2hex(random_bytes(16)) . '.' . $photo->getExtension();
                $photo->move($uploadDir, $newName);

                $updates['photo'] = 'photos/' . $newName;
                $photoUrl         = base_url('uploads/photos/' . $newName);
            }
        }

        // ── 7. Nothing to update ─────────────────
        if (empty($updates)) {
            return $this->respond->error('No fields provided to update.', 422);
        }

        // ── 8. Save to DB ────────────────────────
        $this->users->update($userId, $updates);

        // ── 9. Return updated profile ────────────
        $updated = $this->users
            ->builder()
            ->select('users.id, users.name, users.username, users.email, users.bio,
                      users.phone, users.address, users.photo, users.occupation,
                      users.status, users.`2FA`, users.created_at, users.updated_at,
                      roles.name as role_name, roles.slug as role_slug')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.id', $userId)
            ->get()->getRowArray();

        // Build full photo URL
        if ($updated['photo']) {
            $updated['photo_url'] = base_url('uploads/' . $updated['photo']);
        } else {
            $updated['photo_url'] = null;
        }

        return $this->respond->success($updated, 'Profile updated successfully.');
    }
}
