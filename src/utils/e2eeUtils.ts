import * as SecureStore from 'expo-secure-store';
import forge from 'node-forge';

const PRIVATE_KEY_STORE = 'e2ee_private_key_pem';
const PUBLIC_KEY_STORE = 'e2ee_public_key_pem';

// ─── Key generation & storage ─────────────────────────────────────────────────

export async function generateAndStoreKeyPair(): Promise<string> {
  return new Promise((resolve, reject) => {
    forge.pki.rsa.generateKeyPair({ bits: 2048, workers: -1 }, async (err, keyPair) => {
      if (err) { reject(err); return; }
      try {
        const privatePem = forge.pki.privateKeyToPem(keyPair.privateKey);
        const publicPem = forge.pki.publicKeyToPem(keyPair.publicKey);
        await SecureStore.setItemAsync(PRIVATE_KEY_STORE, privatePem);
        await SecureStore.setItemAsync(PUBLIC_KEY_STORE, publicPem);
        resolve(publicPem);
      } catch (e) {
        reject(e);
      }
    });
  });
}

export async function getStoredPublicKeyPem(): Promise<string | null> {
  return SecureStore.getItemAsync(PUBLIC_KEY_STORE);
}

export async function getOrCreatePublicKeyPem(): Promise<string> {
  const publicPem = await getStoredPublicKeyPem();
  const privatePem = await SecureStore.getItemAsync(PRIVATE_KEY_STORE);
  // Regenerate if either key is missing (handles migration from old key format)
  if (publicPem && privatePem) return publicPem;
  return generateAndStoreKeyPair();
}

// ─── Encrypt (sender side) ────────────────────────────────────────────────────

export type EncryptedPayload = {
  content: string;
  encrypted_key: string;
  iv: string;
  tag: string;
};

export async function encryptMessage(
  plaintext: string,
  recipientPublicKeyPem: string,
): Promise<EncryptedPayload> {
  // 1. Random AES-256 key and 12-byte IV for GCM
  const aesKeyBytes = forge.random.getBytesSync(32);
  const iv = forge.random.getBytesSync(12);

  // 2. AES-256-GCM encrypt
  const cipher = forge.cipher.createCipher('AES-GCM', aesKeyBytes);
  cipher.start({ iv, tagLength: 128 });
  cipher.update(forge.util.createBuffer(forge.util.encodeUtf8(plaintext)));
  cipher.finish();

  const ciphertext = cipher.output.getBytes();
  const tag = (cipher.mode as unknown as { tag: forge.util.ByteStringBuffer }).tag.getBytes();

  // 3. RSA-OAEP encrypt the AES key with recipient's public key
  const recipientKey = forge.pki.publicKeyFromPem(recipientPublicKeyPem);
  const encryptedKey = recipientKey.encrypt(aesKeyBytes, 'RSA-OAEP');

  return {
    content: forge.util.encode64(ciphertext),
    encrypted_key: forge.util.encode64(encryptedKey),
    iv: forge.util.encode64(iv),
    tag: forge.util.encode64(tag),
  };
}

// ─── Decrypt (receiver side) ──────────────────────────────────────────────────

export async function decryptMessage(payload: {
  content: string;
  encrypted_key?: string;
  iv?: string;
  tag?: string;
}): Promise<string | null> {
  // No encryption fields → plain text (file messages, legacy messages)
  if (!payload.encrypted_key || !payload.iv || payload.encrypted_key === 'none') {
    return payload.content;
  }

  try {
    const privatePem = await SecureStore.getItemAsync(PRIVATE_KEY_STORE);
    if (!privatePem) return '[encrypted]';

    // 1. RSA-OAEP decrypt to get raw AES key
    const privateKey = forge.pki.privateKeyFromPem(privatePem);
    const aesKeyBytes = privateKey.decrypt(forge.util.decode64(payload.encrypted_key), 'RSA-OAEP');

    // 2. AES-256-GCM decrypt
    const decipher = forge.cipher.createDecipher('AES-GCM', aesKeyBytes);
    const tagBuffer = payload.tag
      ? forge.util.createBuffer(forge.util.decode64(payload.tag))
      : undefined;

    decipher.start({
      iv: forge.util.decode64(payload.iv),
      tagLength: 128,
      tag: tagBuffer,
    });
    decipher.update(forge.util.createBuffer(forge.util.decode64(payload.content)));

    if (!decipher.finish()) {
      console.warn('[E2EE] GCM tag verification failed');
      return '[encrypted]';
    }

    return forge.util.decodeUtf8(decipher.output.getBytes());
  } catch {
    // Decryption failed — content may not be encrypted (e.g. file caption, old message)
    return payload.content;
  }
}
