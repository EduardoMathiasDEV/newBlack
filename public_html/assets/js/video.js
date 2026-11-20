const play = document.querySelector('#play');
const video = document.querySelector('#vsl');
const statusMessage = document.querySelector('#video-status');

function showVideoError() {
  video.hidden = true;
  play.hidden = false;
  statusMessage.textContent = 'O vídeo de apresentação está indisponível no momento.';
  statusMessage.hidden = false;
}

video.addEventListener('error', showVideoError);
play.addEventListener('click', async () => {
  statusMessage.hidden = true;
  video.src = 'vsl.mp4';
  play.hidden = true;
  video.hidden = false;
  try {
    await video.play();
  } catch (error) {
    if (error.name !== 'NotAllowedError') showVideoError();
  }
});
