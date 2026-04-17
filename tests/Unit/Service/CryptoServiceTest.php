<?php

declare(strict_types=1);

namespace OCA\Files_External_Ethswarm\Tests\Unit\Service;

use OCA\Files_External_Ethswarm\Service\CryptoService;
use PHPUnit\Framework\TestCase;

class CryptoServiceTest extends TestCase {
	private CryptoService $cryptoService;

	protected function setUp(): void {
		parent::setUp();
		$this->cryptoService = new CryptoService();
	}

	public function testMnemonicValidationWithValid12WordMnemonic(): void {
		// Generate a valid mnemonic and validate it
		$mnemonic = $this->cryptoService->generateMnemonic();
		$this->assertTrue($this->cryptoService->validateMnemonic($mnemonic));
	}

	public function testMnemonicValidationWithInvalidMnemonic(): void {
		$this->assertFalse($this->cryptoService->validateMnemonic('invalid word list that does not exist in bip39'));
	}

	public function testMnemonicValidationWithWrongWordCount(): void {
		$this->assertFalse($this->cryptoService->validateMnemonic('abandon'));
		$this->assertFalse($this->cryptoService->validateMnemonic('abandon abandon abandon'));
		$this->assertFalse($this->cryptoService->validateMnemonic(''));
	}

	public function testKeyDerivationProducesConsistentResults(): void {
		$mnemonic = $this->cryptoService->generateMnemonic();
		$salt = $this->cryptoService->generateSalt();

		$key1 = $this->cryptoService->deriveMasterKey($mnemonic, $salt);
		$key2 = $this->cryptoService->deriveMasterKey($mnemonic, $salt);

		$this->assertSame($key1, $key2);
		$this->assertEquals(32, strlen($key1));
	}

	public function testKeyDerivationNormalizesMnemonic(): void {
		$mnemonicLower = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
		$mnemonicUpper = 'ABANDON ABANDON ABANDON ABANDON ABANDON ABANDON ABANDON ABANDON ABANDON ABANDON ABANDON ABOUT';
		$mnemonicMixed = '  Abandon  abandon  ABANDON  abandon  Abandon  abandon  abandon  ABANDON  abandon  abandon  abandon  about  ';
		$salt = $this->cryptoService->generateSalt();

		$key1 = $this->cryptoService->deriveMasterKey($mnemonicLower, $salt);
		$key2 = $this->cryptoService->deriveMasterKey($mnemonicUpper, $salt);
		$key3 = $this->cryptoService->deriveMasterKey($mnemonicMixed, $salt);

		$this->assertSame($key1, $key2, 'Uppercase mnemonic should produce same key as lowercase');
		$this->assertSame($key1, $key3, 'Mixed case with extra whitespace should produce same key');
	}

	public function testDifferentSaltsProduceDifferentKeys(): void {
		$mnemonic = $this->cryptoService->generateMnemonic();
		$salt1 = $this->cryptoService->generateSalt();
		$salt2 = $this->cryptoService->generateSalt();

		$key1 = $this->cryptoService->deriveMasterKey($mnemonic, $salt1);
		$key2 = $this->cryptoService->deriveMasterKey($mnemonic, $salt2);

		$this->assertNotSame($key1, $key2);
	}

	public function testPerFileKeyDerivation(): void {
		$mnemonic = $this->cryptoService->generateMnemonic();
		$salt = $this->cryptoService->generateSalt();
		$masterKey = $this->cryptoService->deriveMasterKey($mnemonic, $salt);

		$fileKey1 = $this->cryptoService->deriveFileKey($masterKey, 'file1.txt');
		$fileKey2 = $this->cryptoService->deriveFileKey($masterKey, 'file2.txt');

		$this->assertNotSame($fileKey1, $fileKey2);
		$this->assertEquals(32, strlen($fileKey1));
		$this->assertEquals(32, strlen($fileKey2));
	}

	public function testEncryptDecryptRoundTrip(): void {
		$mnemonic = $this->cryptoService->generateMnemonic();
		$salt = $this->cryptoService->generateSalt();
		$masterKey = $this->cryptoService->deriveMasterKey($mnemonic, $salt);
		$fileKey = $this->cryptoService->deriveFileKey($masterKey, 'test.txt');

		$plaintext = 'Hello, E2EE World!';
		$encrypted = $this->cryptoService->encrypt($plaintext, $fileKey);

		$this->assertArrayHasKey('ciphertext', $encrypted);
		$this->assertArrayHasKey('nonce', $encrypted);
		$this->assertArrayHasKey('authTag', $encrypted);

		$decrypted = $this->cryptoService->decrypt(
			$encrypted['ciphertext'],
			$fileKey,
			$encrypted['nonce'],
			$encrypted['authTag']
		);

		$this->assertSame($plaintext, $decrypted);
	}

	public function testEncryptDecryptLargeFile(): void {
		$mnemonic = $this->cryptoService->generateMnemonic();
		$salt = $this->cryptoService->generateSalt();
		$masterKey = $this->cryptoService->deriveMasterKey($mnemonic, $salt);
		$fileKey = $this->cryptoService->deriveFileKey($masterKey, 'large.bin');

		// 1MB of data
		$plaintext = str_repeat('A', 1024 * 1024);
		$encrypted = $this->cryptoService->encrypt($plaintext, $fileKey);

		$decrypted = $this->cryptoService->decrypt(
			$encrypted['ciphertext'],
			$fileKey,
			$encrypted['nonce'],
			$encrypted['authTag']
		);

		$this->assertSame($plaintext, $decrypted);
	}

	public function testDecryptWithWrongKeyFails(): void {
		$mnemonic = $this->cryptoService->generateMnemonic();
		$salt = $this->cryptoService->generateSalt();
		$masterKey = $this->cryptoService->deriveMasterKey($mnemonic, $salt);
		$fileKey = $this->cryptoService->deriveFileKey($masterKey, 'test.txt');

		$plaintext = 'Secret data';
		$encrypted = $this->cryptoService->encrypt($plaintext, $fileKey);

		// Derive a different key
		$wrongKey = $this->cryptoService->deriveFileKey($masterKey, 'different.txt');

		$this->expectException(\RuntimeException::class);
		$this->cryptoService->decrypt(
			$encrypted['ciphertext'],
			$wrongKey,
			$encrypted['nonce'],
			$encrypted['authTag']
		);
	}

	public function testDecryptWithTamperedCiphertextFails(): void {
		$mnemonic = $this->cryptoService->generateMnemonic();
		$salt = $this->cryptoService->generateSalt();
		$masterKey = $this->cryptoService->deriveMasterKey($mnemonic, $salt);
		$fileKey = $this->cryptoService->deriveFileKey($masterKey, 'test.txt');

		$plaintext = 'Secret data';
		$encrypted = $this->cryptoService->encrypt($plaintext, $fileKey);

		// Tamper with the ciphertext
		$tamperedCiphertext = $encrypted['ciphertext'];
		$tamperedCiphertext[0] = chr(ord($tamperedCiphertext[0]) ^ 0xFF);

		$this->expectException(\RuntimeException::class);
		$this->cryptoService->decrypt(
			$tamperedCiphertext,
			$fileKey,
			$encrypted['nonce'],
			$encrypted['authTag']
		);
	}

	public function testGenerateMnemonic(): void {
		$mnemonic = $this->cryptoService->generateMnemonic();

		$words = explode(' ', $mnemonic);
		$this->assertCount(12, $words);

		// Each word should be non-empty
		foreach ($words as $word) {
			$this->assertNotEmpty($word);
		}

		// Should be valid according to validateMnemonic
		$this->assertTrue($this->cryptoService->validateMnemonic($mnemonic));
	}

	public function testGenerateSalt(): void {
		$salt = $this->cryptoService->generateSalt();

		// Should be valid base64
		$decoded = base64_decode($salt, true);
		$this->assertNotFalse($decoded);

		// Should decode to 32 bytes
		$this->assertEquals(32, strlen($decoded));

		// Each call should produce a different salt
		$salt2 = $this->cryptoService->generateSalt();
		$this->assertNotSame($salt, $salt2);
	}

	public function testSetAndGetMasterKey(): void {
		$this->assertNull($this->cryptoService->getMasterKey());

		$key = random_bytes(32);
		$this->cryptoService->setMasterKey($key);
		$this->assertSame($key, $this->cryptoService->getMasterKey());
	}

	public function testEncryptionVersionConstant(): void {
		$this->assertEquals(1, CryptoService::ENCRYPTION_VERSION);
	}

	public function testPbkdf2IterationsConstant(): void {
		$this->assertEquals(600000, CryptoService::PBKDF2_ITERATIONS);
	}

	public function testHkdfInfoConstant(): void {
		$this->assertEquals('hejbit-file-key', CryptoService::HKDF_INFO);
	}

	public function testCrossPlatformKeyDerivation(): void {
		// Test vector: known mnemonic + salt must produce a deterministic master key
		// that matches the WebCrypto (JS) implementation exactly.
		$mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
		$salt = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='; // base64 of 32 zero bytes

		$masterKey = $this->cryptoService->deriveMasterKey($mnemonic, $salt);

		// Verify key length
		$this->assertEquals(32, strlen($masterKey), 'Master key must be 32 bytes');

		// Verify the exact key matches the JS/WebCrypto implementation
		// This hex value was computed by the PHP CryptoService and must match
		// the JS deriveMasterKey() output for the same inputs.
		$expectedMasterKeyHex = 'a8abfe756fca0f6e33bb60b1478fde5aefe145f1bda05edf8a46cbb966336ee1';
		$this->assertEquals($expectedMasterKeyHex, bin2hex($masterKey), 'Master key must match JS implementation');

		// Verify deterministic derivation (same inputs → same output)
		$masterKey2 = $this->cryptoService->deriveMasterKey($mnemonic, $salt);
		$this->assertSame($masterKey, $masterKey2, 'Same inputs must produce same master key');

		// Verify case-insensitive mnemonic normalization
		$masterKeyUpper = $this->cryptoService->deriveMasterKey(strtoupper($mnemonic), $salt);
		$this->assertSame($masterKey, $masterKeyUpper, 'Uppercase mnemonic must produce same key after normalization');

		// Derive file key and verify determinism
		$fileKey = $this->cryptoService->deriveFileKey($masterKey, 'test-file-reference');
		$this->assertEquals(32, strlen($fileKey), 'File key must be 32 bytes');

		// Verify the exact file key matches the JS/WebCrypto implementation
		$expectedFileKeyHex = '8772ee7c25b49e80f7508be014f6840e983005ed128f203aa15961c8596ef653';
		$this->assertEquals($expectedFileKeyHex, bin2hex($fileKey), 'File key must match JS implementation');

		$fileKey2 = $this->cryptoService->deriveFileKey($masterKey, 'test-file-reference');
		$this->assertSame($fileKey, $fileKey2, 'Same inputs must produce same file key');
	}

	public function testCrossPlatformEncryptDecryptNoAad(): void {
		// Verify that encryption with no AAD (empty string) works correctly.
		// This must match the WebCrypto implementation which also uses no AAD.
		$mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
		$salt = $this->cryptoService->generateSalt();
		$masterKey = $this->cryptoService->deriveMasterKey($mnemonic, $salt);
		$fileKey = $this->cryptoService->deriveFileKey($masterKey, 'cross-platform-test');

		$plaintext = 'Cross-platform E2EE test data';
		$encrypted = $this->cryptoService->encrypt($plaintext, $fileKey);

		// Verify the encrypted structure
		$this->assertArrayHasKey('ciphertext', $encrypted);
		$this->assertArrayHasKey('nonce', $encrypted);
		$this->assertArrayHasKey('authTag', $encrypted);

		// Verify nonce is valid base64 of 12 bytes
		$decodedNonce = base64_decode($encrypted['nonce'], true);
		$this->assertNotFalse($decodedNonce, 'Nonce must be valid base64');
		$this->assertEquals(12, strlen($decodedNonce), 'Nonce must be 12 bytes');

		// Verify authTag is valid base64 of 16 bytes
		$decodedAuthTag = base64_decode($encrypted['authTag'], true);
		$this->assertNotFalse($decodedAuthTag, 'Auth tag must be valid base64');
		$this->assertEquals(16, strlen($decodedAuthTag), 'Auth tag must be 16 bytes');

		// Verify decryption works
		$decrypted = $this->cryptoService->decrypt(
			$encrypted['ciphertext'],
			$fileKey,
			$encrypted['nonce'],
			$encrypted['authTag']
		);
		$this->assertSame($plaintext, $decrypted);
	}
}