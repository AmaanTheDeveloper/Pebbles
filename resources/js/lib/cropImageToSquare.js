/**
 * Center-crop an image file to a 1:1 square JPEG (client preview / preflight).
 * Server remains authoritative and re-crops with GD.
 *
 * @param {File} file
 * @param {number} [maxSize=2048]
 * @returns {Promise<{ previewUrl: string, blob: Blob, width: number, height: number }>}
 */
export function cropImageFileToSquare(file, maxSize = 2048) {
    return new Promise((resolve, reject) => {
        if (!file || !file.type.startsWith('image/')) {
            reject(new Error('Image must be JPG, PNG, or WEBP.'));
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            try {
                const side = Math.min(image.naturalWidth, image.naturalHeight);
                if (side < 600) {
                    URL.revokeObjectURL(objectUrl);
                    reject(new Error('Image resolution is too low. Minimum size is 600×600 pixels.'));
                    return;
                }

                const target = Math.min(maxSize, side);
                const sx = Math.floor((image.naturalWidth - side) / 2);
                const sy = Math.floor((image.naturalHeight - side) / 2);
                const canvas = document.createElement('canvas');
                canvas.width = target;
                canvas.height = target;
                const context = canvas.getContext('2d');
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, target, target);
                context.drawImage(image, sx, sy, side, side, 0, 0, target, target);

                canvas.toBlob(
                    (blob) => {
                        URL.revokeObjectURL(objectUrl);
                        if (!blob) {
                            reject(new Error('Unable to prepare square image preview.'));
                            return;
                        }

                        resolve({
                            previewUrl: URL.createObjectURL(blob),
                            blob,
                            width: target,
                            height: target,
                        });
                    },
                    'image/jpeg',
                    0.92,
                );
            } catch (error) {
                URL.revokeObjectURL(objectUrl);
                reject(error);
            }
        };

        image.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            reject(new Error('Unable to read the selected image.'));
        };

        image.src = objectUrl;
    });
}
