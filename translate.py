import json
import time
import sys
from deep_translator import GoogleTranslator

with open('missing.json', 'r', encoding='utf-8') as f:
    missing = json.load(f)

path_en = 'resources/js/locales/en.json'
with open(path_en, 'r', encoding='utf-8') as f:
    en_dict = json.load(f)

translator = GoogleTranslator(source='vi', target='en')
new_dict = {}

to_translate = [t for t in missing if len(t) < 150 and '\n' not in t]

print(f"Translating {len(to_translate)} strings...", flush=True)

batch_size = 30
for i in range(0, len(to_translate), batch_size):
    batch = to_translate[i:i+batch_size]
    print(f"Translating batch {i} to {i+len(batch)}...", flush=True)
    try:
        translated = translator.translate_batch(batch)
        for vi_text, en_text in zip(batch, translated):
            new_dict[vi_text] = en_text
    except Exception as e:
        print(f"Error at batch {i}: {e}", flush=True)
        # fallback to individual
        for vi_text in batch:
            try:
                new_dict[vi_text] = translator.translate(vi_text)
                time.sleep(0.5)
            except:
                pass
    time.sleep(1)

en_dict.update(new_dict)

with open(path_en, 'w', encoding='utf-8') as f:
    json.dump(en_dict, f, ensure_ascii=False, indent=2)

print("Done translating and saved to en.json", flush=True)
