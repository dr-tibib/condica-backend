class CameraService {
  private videoElement: HTMLVideoElement | null = null;
  private stream: MediaStream | null = null;

  async initialize(): Promise<void> {
    if (this.stream) {
      return;
    }

    try {
      this.stream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: 'user',
          width: 1280,
          height: 720,
        },
      });

      this.videoElement = document.createElement('video');
      this.videoElement.srcObject = this.stream;
      this.videoElement.style.display = 'none';
      document.body.appendChild(this.videoElement);
      await this.videoElement.play();
    } catch (error) {
      console.error('Error initializing camera:', error);
      throw new Error('Camera initialization failed.');
    }
  }

  capturePhoto(): Promise<Blob> {
    return new Promise((resolve, reject) => {
      if (!this.videoElement) {
        return reject(new Error('Camera not initialized.'));
      }

      const canvas = document.createElement('canvas');
      canvas.width = this.videoElement.videoWidth;
      canvas.height = this.videoElement.videoHeight;
      const context = canvas.getContext('2d');
      if (context) {
        context.drawImage(this.videoElement, 0, 0, canvas.width, canvas.height);
        canvas.toBlob((blob) => {
          if (blob) {
            resolve(blob);
          } else {
            reject(new Error('Failed to create blob from canvas.'));
          }
        }, 'image/jpeg', 0.85);
      } else {
        reject(new Error('Failed to get 2D context from canvas.'));
      }
    });
  }

  captureVideo(duration: number): Promise<Blob> {
    return new Promise((resolve, reject) => {
      if (!this.stream) {
        return reject(new Error('Camera not initialized.'));
      }

      const mediaRecorder = new MediaRecorder(this.stream);
      const chunks: BlobPart[] = [];

      mediaRecorder.ondataavailable = (event) => {
        chunks.push(event.data);
      };

      mediaRecorder.onstop = () => {
        const blob = new Blob(chunks, { type: 'video/mp4' });
        resolve(blob);
      };

      mediaRecorder.onerror = (event) => {
        reject(new Error(`MediaRecorder error: ${(event as any).error.name}`));
      };

      mediaRecorder.start();

      setTimeout(() => {
        mediaRecorder.stop();
      }, duration * 1000);
    });
  }

  stop(): void {
    if (this.stream) {
      this.stream.getTracks().forEach((track) => track.stop());
      this.stream = null;
    }
    if (this.videoElement) {
      document.body.removeChild(this.videoElement);
      this.videoElement = null;
    }
  }
}

const cameraService = new CameraService();
export default cameraService;