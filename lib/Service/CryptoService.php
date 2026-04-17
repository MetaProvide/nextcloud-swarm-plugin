<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2025, MetaProvide Holding EKF
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Files_External_Ethswarm\Service;

class CryptoService {
	public const ENCRYPTION_VERSION = 1;
	public const PBKDF2_ITERATIONS = 600000;
	public const PBKDF2_HASH_ALGO = 'sha256';
	public const PBKDF2_KEY_LENGTH = 32;
	public const HKDF_INFO = 'hejbit-file-key';
	public const HKDF_HASH_ALGO = 'sha256';
	public const HKDF_KEY_LENGTH = 32;
	public const AES_GCM_NONCE_LENGTH = 12;
	public const AES_GCM_TAG_LENGTH = 16;

	private ?string $masterKey = null;

	/**
	 * Validate a BIP-39 mnemonic (12 or 24 words).
	 */
	public function validateMnemonic(string $mnemonic): bool {
		$words = preg_split('/\s+/', trim($mnemonic));
		if ($words === false) {
			return false;
		}

		$wordCount = count($words);
		if (12 !== $wordCount && 24 !== $wordCount) {
			return false;
		}

		$wordlist = $this->loadBip39Wordlist();
		if (null === $wordlist) {
			// If wordlist is not available, do basic validation
			return true;
		}

		foreach ($words as $word) {
			if (!in_array(strtolower($word), $wordlist, true)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Derive master key from mnemonic + base64-encoded salt using PBKDF2-SHA256.
	 * Mnemonic is normalized (lowercase, trimmed) to match WebCrypto implementation.
	 */
	public function deriveMasterKey(string $mnemonic, string $salt): string {
		$decodedSalt = base64_decode($salt, true);
		if (false === $decodedSalt) {
			throw new \InvalidArgumentException('Invalid base64-encoded salt');
		}

		// Normalize mnemonic: lowercase, trim whitespace, collapse multiple spaces
		$normalizedMnemonic = strtolower(preg_replace('/\s+/', ' ', trim($mnemonic)));

		$key = hash_pbkdf2(
			self::PBKDF2_HASH_ALGO,
			$normalizedMnemonic,
			$decodedSalt,
			self::PBKDF2_ITERATIONS,
			self::PBKDF2_KEY_LENGTH,
			true
		);

		return $key;
	}

	/**
	 * Derive per-file key using HKDF-SHA256.
	 */
	public function deriveFileKey(string $masterKey, string $fileReference): string {
		return hash_hkdf(
			self::HKDF_HASH_ALGO,
			$masterKey,
			self::HKDF_KEY_LENGTH,
			self::HKDF_INFO,
			$fileReference
		);
	}

	/**
	 * Encrypt with AES-256-GCM.
	 * No additional authenticated data (AAD) is used, matching the WebCrypto implementation.
	 *
	 * @return array{ciphertext: string, nonce: string, authTag: string}
	 */
	public function encrypt(string $plaintext, string $fileKey): array {
		$nonce = random_bytes(self::AES_GCM_NONCE_LENGTH);

		if (function_exists('sodium_crypto_aead_aes256gcm_encrypt') && sodium_crypto_aead_aes256gcm_is_available()) {
			// sodium_crypto_aead_aes256gcm_encrypt returns ciphertext || tag
			// 2nd arg (AD) is empty string — no additional authenticated data
			$encrypted = sodium_crypto_aead_aes256gcm_encrypt(
				$plaintext,
				'',
				$nonce,
				$fileKey
			);

			// Split ciphertext and tag (tag is last 16 bytes)
			$ciphertext = substr($encrypted, 0, -self::AES_GCM_TAG_LENGTH);
			$authTag = substr($encrypted, -self::AES_GCM_TAG_LENGTH);
		} else {
			// Fallback to OpenSSL
			$ciphertext = openssl_encrypt(
				$plaintext,
				'aes-256-gcm',
				$fileKey,
				OPENSSL_RAW_DATA,
				$nonce,
				$authTag,
				'', // no AAD — must match WebCrypto (empty string)
				self::AES_GCM_TAG_LENGTH
			);

			if (false === $ciphertext) {
				throw new \RuntimeException('AES-256-GCM encryption failed: ' . openssl_error_string());
			}
		}

		return [
			'ciphertext' => $ciphertext,
			'nonce' => base64_encode($nonce),
			'authTag' => base64_encode($authTag),
		];
	}

	/**
	 * Decrypt with AES-256-GCM.
	 * No additional authenticated data (AAD) is used, matching the WebCrypto implementation.
	 */
	public function decrypt(string $ciphertext, string $fileKey, string $nonce, string $authTag): string {
		$decodedNonce = base64_decode($nonce, true);
		if (false === $decodedNonce) {
			throw new \InvalidArgumentException('Invalid base64-encoded nonce');
		}

		$decodedAuthTag = base64_decode($authTag, true);
		if (false === $decodedAuthTag) {
			throw new \InvalidArgumentException('Invalid base64-encoded auth tag');
		}

		if (function_exists('sodium_crypto_aead_aes256gcm_decrypt') && sodium_crypto_aead_aes256gcm_is_available()) {
			// sodium expects ciphertext || tag concatenated
			$combined = $ciphertext.$decodedAuthTag;

			// 2nd arg (AD) is empty string — no additional authenticated data
			$decrypted = sodium_crypto_aead_aes256gcm_decrypt(
				$combined,
				'',
				$decodedNonce,
				$fileKey
			);

			if (false === $decrypted) {
				throw new \RuntimeException('Decryption failed: authentication tag verification failed');
			}
		} else {
			// Fallback to OpenSSL
			$decrypted = openssl_decrypt(
				$ciphertext,
				'aes-256-gcm',
				$fileKey,
				OPENSSL_RAW_DATA,
				$decodedNonce,
				$decodedAuthTag,
				'' // no AAD — must match WebCrypto (empty string)
			);

			if (false === $decrypted) {
				throw new \RuntimeException('Decryption failed: ' . openssl_error_string());
			}
		}

		return $decrypted;
	}

	/**
	 * Generate random base64-encoded 32-byte salt.
	 */
	public function generateSalt(): string {
		return base64_encode(random_bytes(32));
	}

	/**
	 * Generate random 12-word BIP-39 mnemonic.
	 */
	public function generateMnemonic(): string {
		// Generate 128 bits of entropy
		$entropy = random_bytes(16);

		// Calculate SHA-256 checksum
		$checksum = hash('sha256', $entropy, true);

		// Take first 4 bits of checksum
		$checksumBits = (ord($checksum[0]) >> 4) & 0x0F;

		// Combine entropy + checksum bits
		$entropyBits = '';
		for ($i = 0; $i < 16; ++$i) {
			$entropyBits .= str_pad(decbin(ord($entropy[$i])), 8, '0', STR_PAD_LEFT);
		}
		$checksumBitStr = str_pad(decbin($checksumBits), 4, '0', STR_PAD_LEFT);
		$combinedBits = $entropyBits.$checksumBitStr;

		// Split into 11-bit groups
		$wordlist = $this->loadBip39Wordlist();
		if (null === $wordlist) {
			throw new \RuntimeException('BIP-39 wordlist not available');
		}

		$words = [];
		for ($i = 0; $i < 12; ++$i) {
			$bitGroup = substr($combinedBits, $i * 11, 11);
			$index = bindec($bitGroup);
			$words[] = $wordlist[$index];
		}

		return implode(' ', $words);
	}

	/**
	 * Get current master key.
	 */
	public function getMasterKey(): ?string {
		return $this->masterKey;
	}

	/**
	 * Set master key directly.
	 */
	public function setMasterKey(string $masterKey): void {
		$this->masterKey = $masterKey;
	}

	/**
	 * Load BIP-39 English wordlist.
	 *
	 * @return array<string>|null
	 */
	private function loadBip39Wordlist(): ?array {
		static $wordlist = null;

		if (null !== $wordlist) {
			return $wordlist;
		}

		$wordlistPath = __DIR__.'/../../data/bip39-english.txt';
		if (!file_exists($wordlistPath)) {
			return null;
		}

		$content = file_get_contents($wordlistPath);
		if (false === $content) {
			return null;
		}

		$wordlist = array_map('trim', explode("\n", trim($content)));

		return $wordlist;
	}
}