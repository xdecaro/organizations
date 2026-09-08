from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
TEXT_SUFFIXES = {'.php', '.xml', '.ini', '.md', '.json', '.yml', '.yaml', '.txt', '.sql', '.sh'}
TEXT_NAMES = {'VERSION'}

for path in sorted(ROOT.rglob('*')):
    if not path.is_file() or '.git' in path.parts or '.github' in path.parts or path == Path(__file__):
        continue
    if path.suffix.lower() not in TEXT_SUFFIXES and path.name not in TEXT_NAMES:
        continue
    text = path.read_text(encoding='utf-8')
    new = text.replace('Xdecaro\\', 'xdecaro\\').replace('Xdecaro.', 'xdecaro.')
    if path.name == 'VERSION' or path.suffix.lower() in {'.xml', '.json'}:
        new = new.replace('0.2.0', '0.3.0')
    if path.name == 'pkg_xdecaroorganizations.xml' and 'updates' in path.parts:
        new = re.sub(r'<sha256>[^<]*</sha256>', '', new)
    if new != text:
        path.write_text(new, encoding='utf-8')
