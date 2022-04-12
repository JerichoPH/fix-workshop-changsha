import json

import arrow
from openpyxl import load_workbook, Workbook

json_file = json.load(fp=open(file="继电器统计.json", encoding="utf-8", mode="r"))
status_dict = {
    'BUY_IN': '新入所',
    'INSTALLING': '安装中',
    'INSTALLED': '安装完成',
    'FIXING': '检修中',
    'FIXED': '可用',
    'RETURN_FACTORY': '返厂维修',
    'FACTORY_RETURN': '返厂入所',
    'SCRAP': '报废',
}

new_excel = Workbook()
sheet = new_excel["Sheet"]
# 写入表头
sheet.append(("型号", "类型", "所编号", "供应商", "唯一编号", "安装位置", "安装时间", "主/备用", "状态", "在库状态",))
# 写入数据
for row in json_file:
    # print(row)
    entire_model = "%s(%s)" % (row['entire_model_name'], row['entire_model_unique_code'])
    category = row['category_name']
    serial_number = row['serial_number']
    factory_name = row['factory_name']
    identity_code = row['identity_code']
    install_location = "%s %s" % (row['maintain_station_name'], row['maintain_location_code'])
    is_main = "主用" if row['is_main'] == 1 else "备用"
    in_warehouse = "在库" if row['in_warehouse'] == 1 else "库外"
    last_installed_at = arrow.get(row['last_installed_time']).format("YYYY-MM-DD HH:mm:ss")
    status = status_dict[row['status']]
    # print(entire_model, category, serial_number, factory_name, identity_code, install_location, is_main, in_warehouse)
    sheet.append((entire_model, category, serial_number, factory_name, identity_code, install_location, last_installed_at, is_main, status, in_warehouse,))
new_excel.save("继电器统计.xlsx")
