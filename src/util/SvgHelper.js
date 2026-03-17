const SvgHelper = {
	convert(svg) {
		if (typeof svg !== "string") {
			return "";
		}

		const normalizedSvg = svg.trim();

		if (normalizedSvg.startsWith("<svg")) {
			return normalizedSvg;
		}

		if (!normalizedSvg.startsWith("data:image/svg+xml")) {
			return normalizedSvg;
		}

		const base64Prefix = "data:image/svg+xml;base64,";
		if (normalizedSvg.startsWith(base64Prefix)) {
			const base64Data = normalizedSvg.slice(base64Prefix.length);
			const binaryString = atob(base64Data);
			const bytes = new Uint8Array(binaryString.length);
			for (let i = 0; i < binaryString.length; i++) {
				bytes[i] = binaryString.charCodeAt(i);
			}
			return new TextDecoder("utf-8").decode(bytes);
		}

		const svgPayload = normalizedSvg.split(",")[1];
		return svgPayload ? decodeURIComponent(svgPayload) : normalizedSvg;
	},
};

export default SvgHelper;
