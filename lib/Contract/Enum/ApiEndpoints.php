<?php

namespace OCA\Files_External_Ethswarm\Contract\Enum;

enum ApiEndpoints: string {
	case DOWNLOAD = '/api/download';
	case GATEWAY_LINK = '/api/gateway-link';
	case IMPORT = '/api/import';
	case UPLOAD = '/api/upload';
	case READINESS = '/api/readiness';
}
