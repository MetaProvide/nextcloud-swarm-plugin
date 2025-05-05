const SvgHelper = {
	convert(svg) {
		const base64Data = svg.split(",")[1];
		const binaryString = atob(base64Data);
		const bytes = new Uint8Array(binaryString.length);
		for (let i = 0; i < binaryString.length; i++) {
			bytes[i] = binaryString.charCodeAt(i);
		}
		const decoder = new TextDecoder('utf-8');
		return decoder.decode(bytes);
	},
};

export default SvgHelper;
