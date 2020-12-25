#pragma once
#include "method.h"
#include <vector>
#include <QString>
#include <QJsonArray>

namespace nano
{
	class service
	{
	public:
		virtual QString shortName() const = 0;
		virtual QString longName() const = 0;
		virtual QString version() const = 0;
		virtual QString description() const = 0;
		virtual std::vector<method> methods() const = 0;

		method operator[](const QString& name)
		{
			const auto mtds = methods();
			const auto iter = std::find_if(mtds.begin(), mtds.end(), [name](auto& mtd) { return mtd.name == name; });
			if (iter == mtds.end()) throw std::exception(QString("'%1' does not contain a method called '%2'").arg(shortName(), name).toStdString().c_str());
			auto m = *iter;
			m.service = this;
			return m;
		}

		std::vector<QString> methodNames() const
		{
			std::vector<QString> r;
			for (auto& mtd : methods()) r.push_back(mtd.name);
			return r;
		}

		QJsonObject definition() const
		{
			QJsonArray mtds;
			for (auto& mtd : methods())
			{
				QJsonArray pms;
				for (auto& pm : mtd.parameters)
				{
					QJsonObject jpm{
						{"name", pm.name},
						{"optional", pm.optional}
					};
					pms.append(jpm);
				}

				QJsonObject jmtd{
					{"name", mtd.name},
					{"parameters", pms}
				};
				mtds.append(jmtd);
			}

			QJsonObject obj{
				{"shortName", shortName()},
				{"longName", longName()},
				{"version", version()},
				{"description", description()},
				{"methods", mtds}
			};

			return obj;
		}
	};
}