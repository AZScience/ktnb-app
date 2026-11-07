import os
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace(
    '} catch (\\Exception $e) {\n                }\n            } else if ($source === \'email\')',
    '} catch (\\Exception $e) { $emailError = $e->getMessage(); }\n            } else if ($source === \'email\')'
)
c = c.replace(
    '} catch (\\Exception $e) {\n                    \\Illuminate\\Support\\Facades\\Log::error("LCMS Exception: " . $e->getMessage());\n                }\n            } else if ($source === \'lcms\')',
    '} catch (\\Exception $e) { $lcmsError = $e->getMessage(); \\Illuminate\\Support\\Facades\\Log::error("LCMS Exception: " . $e->getMessage()); }\n            } else if ($source === \'lcms\')'
)

with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)
print('Done')
