<?php

namespace OCA\Files_External_Ethswarm\Contract\Enum;

enum ApiEndpoints: string {
	case DOWNLOAD = '/api/download';
	case UPLOAD = '/api/upload';
	case READINESS = '/api/readiness';
}
