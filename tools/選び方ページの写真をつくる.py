"""選び方ページの写真をつくります。

  余白を切りつめて、まわりに少しだけ余白を足し、
  4:3の枠にぴったり収めます。
  無理に引きのばさないので、商品がいちばん大きく見えます。
"""
import subprocess, sys, os

G = '/home/claude/yamakishi-site/assets/img/guide'
U = '/root/.claude/uploads/d0e21316-8e50-54e5-9124-4594c1b4b077'
P = '/home/claude/yamakishi-site/assets/img/products'

SRC = {
    'gas':      (f'{U}/40ffc5d1-image.jpg', ['-gravity','south','-chop','0x34','+repage']),
    'enefarm':  (f'{U}/99de20a0-image.jpg', []),
    'oil':      (f'{P}/otq-c4706say/otq-c4706say-main.jpg', []),
    'ecocute':  (f'{P}/srt-s377u/srt-s377u-main.jpg', []),
    'electric': (f'{U}/be5d42e0-image.jpg', []),
}

for k, (src, pre) in SRC.items():
    tmp = f'/tmp/_c_{k}.png'
    subprocess.run(['convert', src, *pre, '-background','white','-alpha','remove',
                    '-fuzz','8%','-trim','+repage',
                    '-bordercolor','white','-border','3%', tmp], check=True)
    w, h = map(int, subprocess.run(['identify','-format','%w %h', tmp],
               capture_output=True, text=True, check=True).stdout.split())
    # 4:3 になるように、足りないほうだけ白でうめます
    W, H = max(w, round(h*4/3)), max(h, round(w*3/4))
    subprocess.run(['convert', tmp, '-gravity','center','-background','white',
                    '-extent', f'{W}x{H}', '-quality','90', f'{G}/{k}.jpg'], check=True)
    subprocess.run(['convert', f'{G}/{k}.jpg', '-quality','82', f'{G}/{k}.webp'], check=True)
    os.remove(tmp)
    print(f'{k:10} {W}x{H}')
